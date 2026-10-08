<?php

// Standalone integration harness: php tests/Concurrency/mysql-bookings.php
// Uses the local MySQL credentials, but NEVER connects to the application database.
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$worker = ($argv[1] ?? '') === 'worker';
$database = $worker ? $argv[2] : 'aurora_concurrency_test_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
if (! preg_match('/^aurora_concurrency_test_[a-zA-Z0-9_]+$/', $database)) {
    throw new RuntimeException('Unsafe database name.');
}
$mysql = config('database.connections.mysql');
$mysql['url'] = null;
$mysql['database'] = $database;
config(['database.default' => 'mysql', 'database.connections.mysql' => $mysql,
    'app.env' => 'testing', 'cache.default' => 'array', 'session.driver' => 'array',
    'logging.default' => 'stderr']);
DB::purge('mysql');
$wait = function (string $file): void {
    $deadline = microtime(true) + 20;
    while (! is_file($file)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Barrier timed out: '.$file);
        }
        usleep(10000);
    }
};

if ($worker) {
    [$script, $mode, $dbName, $directory, $index] = $argv;
    $input = json_decode(file_get_contents($directory.'/input.json'), true, flags: JSON_THROW_ON_ERROR);
    config(['bookings.source_availability_policies' => $input['policies']]);
    $connectionId = DB::selectOne('SELECT CONNECTION_ID() id')->id;
    $lockTimes = [];
    $customerBarrierUsed = false;
    DB::listen(function ($query) use ($directory, $index, $input, $wait, &$lockTimes, &$customerBarrierUsed): void {
        if (str_contains($query->sql, '`services`') && str_contains($query->sql, 'for update')) {
            $lockTimes[] = $query->time;
            // Hold the first acquired service lock long enough for its competitor to wait.
            usleep(300000);
        }
        if ($input['customer_barrier'] && ! $customerBarrierUsed && str_contains($query->sql, 'LOWER(TRIM(email))')) {
            $customerBarrierUsed = true;
            touch($directory.'/customer-'.$index);
            $wait($directory.'/customer-'.(1 - (int) $index));
        }
    });
    touch($directory.'/ready-'.$index);
    $wait($directory.'/go');
    $payload = $input['requests'][$index];
    $token = $payload['token'];
    unset($payload['token']);
    $request = Illuminate\Http\Request::create('/api/bookings', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_AURORA_TOKEN' => $token,
    ], json_encode($payload));
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    file_put_contents($directory.'/result-'.$index.'.json', json_encode([
        'connection_id' => $connectionId, 'http' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true), 'service_lock_ms' => $lockTimes,
    ], JSON_PRETTY_PRINT));
    $kernel->terminate($request, $response);
    exit;
}

// Connect to the server without selecting aurora_db, and create a unique empty schema.
$dsn = 'mysql:host='.$mysql['host'].';port='.$mysql['port'].';charset=utf8mb4';
$admin = new PDO($dsn, $mysql['username'], $mysql['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$admin->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo 'Isolated database: '.$database.PHP_EOL;
Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]);
echo Artisan::output();
$metadata = DB::selectOne('SELECT VERSION() version, @@transaction_isolation isolation_level');
$engines = DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('bookings','customers','services','sources')", [$database]);
$root = sys_get_temp_dir().'/'.$database;
mkdir($root);
$report = ['database' => $database, 'server' => $metadata, 'engines' => $engines, 'runs' => []];
$failures = 0;
foreach (['duplicate', 'blocking', 'new_customer', 'non_blocking'] as $scenario) {
    for ($round = 0; $round < 5; $round++) {
        $directory = $root.'/'.$scenario.'-'.$round;
        mkdir($directory);
        $service = Service::create(['name' => $scenario.'-'.$round]);
        $secondService = $scenario === 'new_customer' ? Service::create(['name' => 'second-'.$round]) : $service;
        $sources = [];
        foreach ([$service, $secondService] as $index => $selectedService) {
            $source = Source::create(['name' => $scenario, 'slug' => $scenario.'-'.$round.'-'.$index,
                'api_token' => bin2hex(random_bytes(16)), 'is_active' => true]);
            $source->services()->attach($selectedService, ['duration_minutes' => 30, 'is_active' => true]);
            $source->openingHours()->create(['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '12:00']);
            $sources[] = $source;
        }
        $date = now()->addWeek()->next('Monday')->toDateString();
        $requests = [];
        foreach ($sources as $index => $source) {
            $requests[] = ['token' => $source->api_token, 'first_name' => 'Test',
                'email' => $scenario.'-'.$round.($scenario === 'blocking' || $scenario === 'non_blocking' ? '-'.$index : '').'@example.test',
                'service_id' => $index === 0 ? $service->id : $secondService->id,
                'booking_mode' => 'time_slots', 'requested_date' => $date, 'booking_time' => '09:00',
                'availability_policy' => $scenario === 'non_blocking' ? 'non_blocking' : 'blocking'];
        }
        file_put_contents($directory.'/input.json', json_encode(['requests' => $requests,
            'customer_barrier' => $scenario === 'new_customer',
            'policies' => array_fill_keys(array_map(fn ($source) => $source->id, $sources), ['blocking', 'non_blocking'])]));
        $processes = [];
        for ($index = 0; $index < 2; $index++) {
            $processes[] = proc_open([PHP_BINARY, __FILE__, 'worker', $database, $directory, (string) $index],
                [0 => ['pipe', 'r'], 1 => ['file', $directory.'/stdout-'.$index, 'w'],
                    2 => ['file', $directory.'/stderr-'.$index, 'w']], $pipes);
        }
        $wait($directory.'/ready-0');
        $wait($directory.'/ready-1');
        touch($directory.'/go');
        foreach ($processes as $process) {
            proc_close($process);
        }
        $results = [];
        for ($index = 0; $index < 2; $index++) {
            $file = $directory.'/result-'.$index.'.json';
            $results[] = is_file($file) ? json_decode(file_get_contents($file), true) : ['http' => 0, 'error' => file_get_contents($directory.'/stderr-'.$index)];
        }
        $statuses = array_column($results, 'http');
        sort($statuses);
        $expected = in_array($scenario, ['duplicate', 'blocking']) ? [201, 422] : [201, 201];
        $bookingCount = Booking::whereIn('service_id', [$service->id, $secondService->id])->count();
        $customerCount = Customer::whereIn('email', array_column($requests, 'email'))->count();
        $codes = array_map(fn ($result) => $result['body']['code'] ?? null, $results);
        $pass = $statuses === $expected && $bookingCount === count(array_filter($expected, fn ($status) => $status === 201))
            && $customerCount === (in_array($scenario, ['duplicate', 'blocking', 'new_customer']) ? 1 : 2)
            && ($scenario !== 'duplicate' || in_array('booking_already_exists', $codes))
            && ($scenario !== 'blocking' || in_array('booking_slot_unavailable', $codes))
            && ($results[0]['connection_id'] ?? null) !== ($results[1]['connection_id'] ?? null);
        $entry = compact('scenario', 'round', 'pass', 'bookingCount', 'customerCount', 'results');
        $report['runs'][] = $entry;
        echo json_encode($entry, JSON_UNESCAPED_UNICODE).PHP_EOL;
        if (! $pass) {
            $failures++;
        }
    }
}
$report['failures'] = $failures;
file_put_contents($root.'/report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Report: '.$root.'/report.json'.PHP_EOL;
echo 'Database retained for inspection; no application data accessed.'.PHP_EOL;
exit($failures ? 1 : 0);
