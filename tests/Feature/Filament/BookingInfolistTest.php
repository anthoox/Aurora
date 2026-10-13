<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Source;
use App\Models\User;
use App\Services\BookingScheduler;
use App\Services\GoogleCalendarService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingInfolistTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        // The historical rollback makes starts_at NOT NULL; remove test fixtures first.
        DB::table('bookings')->delete();
        parent::tearDown();
    }

    private function booking(array $attributes = []): Booking
    {
        $source = Source::create(['name' => 'Web', 'slug' => 'web', 'api_token' => 'token', 'is_active' => true]);
        $service = Service::create(['name' => 'Consulta']);
        $source->services()->attach($service, ['is_active' => true, 'duration_minutes' => 45]);
        $customer = Customer::create(['first_name' => 'Ana', 'email' => 'ana@example.com']);

        return app(BookingScheduler::class)->createManual([
            'customer_id' => $customer->id, 'source_id' => $source->id, 'service_id' => $service->id,
            'booking_mode' => 'time_slots', 'status' => 'pendiente',
            'requested_date' => now()->addDays(2)->toDateString(),
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(10, 45),
            'notes' => 'Notas originales', 'customer_message' => 'Mensaje original',
            ...$attributes,
        ])->fresh();
    }

    private static function fields(): array
    {
        return [
            ['id' => 'empresa', 'label' => 'Compañía', 'type' => 'text', 'value' => 'Ágora 日本語'],
            ['id' => 'empleados', 'label' => 'Empleados', 'type' => 'number', 'value' => 0],
            ['id' => 'cantidad', 'label' => 'Cantidad', 'type' => 'number', 'value' => 25],
            ['id' => 'decimal', 'label' => 'Decimal', 'type' => 'number', 'value' => 2.5],
            ['id' => 'acepta', 'label' => 'Acepta', 'type' => 'checkbox', 'value' => true],
            ['id' => 'rechaza', 'label' => 'Rechaza', 'type' => 'checkbox', 'value' => false],
        ];
    }

    #[DataProvider('emptyFields')]
    public function test_section_is_absent_for_old_or_empty_bookings(array $attributes): void
    {
        Livewire::test(BookingInfolistPreview::class, ['record' => $this->booking($attributes)])
            ->assertSee('Resumen de reserva')->assertSee('Cliente')
            ->assertSee('Servicio y origen')->assertSee('Google Calendar')
            ->assertSee('Notas internas')->assertSee('Mensaje del cliente')
            ->assertSee('Notas originales')->assertSee('Mensaje original')
            ->assertDontSee('Campos del formulario');
    }

    public static function emptyFields(): iterable
    {
        yield 'old booking' => [[]];
        yield 'null' => [['custom_fields' => null]];
        yield 'empty' => [['custom_fields' => []]];
    }

    public function test_labels_and_values_are_read_only_and_preserve_the_record(): void
    {
        $record = $this->booking(['custom_fields' => self::fields()]);
        $original = $record->getRawOriginal();
        $preview = Livewire::test(BookingInfolistPreview::class, ['record' => $record])
            ->assertSee('Campos del formulario')->assertSee('Sí')->assertSee('No');
        $schema = $preview->instance()->infolist(Schema::make($preview->instance()));
        $sections = $schema->getComponents();
        $repeatable = $sections[6]->getChildSchema()->getComponents()[0];

        foreach ($repeatable->getItems() as $index => $item) {
            $field = self::fields()[$index];
            $expected = is_bool($field['value']) ? ($field['value'] ? 'Sí' : 'No') : (string) $field['value'];
            $preview->assertSee($field['label'])->assertSee($expected);
            [$label, $answer] = $item->getComponents();
            $this->assertInstanceOf(TextEntry::class, $label);
            $this->assertInstanceOf(TextEntry::class, $answer);
            $this->assertFalse($label->isHtml());
            $this->assertFalse($answer->isHtml());
            $this->assertSame($expected, $answer->formatState($answer->getState()));
        }

        $this->assertSame($original, $record->fresh()->getRawOriginal());
    }

    public function test_html_in_labels_and_values_is_escaped(): void
    {
        $label = '<script>alert("label")</script>';
        $value = '<img src=x onerror="alert(1)"><b>Respuesta</b>';
        $record = $this->booking(['custom_fields' => [[
            'id' => 'unsafe', 'label' => $label, 'type' => 'text', 'value' => $value,
        ]]]);

        Livewire::test(BookingInfolistPreview::class, ['record' => $record])
            ->assertSee(e($label), escape: false)->assertSee(e($value), escape: false)
            ->assertDontSee($label, escape: false)->assertDontSee($value, escape: false);
        $this->assertSame($value, $record->fresh()->custom_fields[0]['value']);
    }

    #[DataProvider('bookingModes')]
    public function test_filament_edit_confirm_and_cancel_preserve_custom_fields(string $mode): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $calendar = Mockery::mock(GoogleCalendarService::class);
        $calendar->shouldReceive('createEventFromBooking')->once()->andReturn('event-test');
        $calendar->shouldReceive('deleteEvent')->once()->with('event-test')->andReturnNull();
        $this->app->instance(GoogleCalendarService::class, $calendar);
        $record = $this->booking([
            'booking_mode' => $mode, 'custom_fields' => self::fields(),
            'requested_date' => $mode === 'request_only' ? null : now()->addDays(2)->toDateString(),
            ...($mode === 'time_slots' ? [] : ['starts_at' => null, 'ends_at' => null]),
        ]);
        $originalJson = $record->getRawOriginal('custom_fields');

        Livewire::test(EditBooking::class, ['record' => $record->id])
            ->fillForm(['notes' => 'Notas editadas'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Notas editadas', $record->fresh()->notes);
        $this->assertSame($originalJson, $record->fresh()->getRawOriginal('custom_fields'));

        $view = Livewire::test(ViewBooking::class, ['record' => $record->id]);
        if ($mode === 'time_slots') {
            $view->callAction('confirmRequest')->assertHasNoActionErrors();
        } else {
            $view->callAction('scheduleAndConfirm', data: [
                'booking_date' => now()->addDays(2)->toDateString(),
                'start_time' => '10:00', 'duration_minutes' => 45,
            ])->assertHasNoActionErrors();
        }
        $this->assertSame('confirmada', $record->fresh()->status);
        $this->assertSame('event-test', $record->fresh()->google_event_id);
        $this->assertSame($originalJson, $record->fresh()->getRawOriginal('custom_fields'));

        Livewire::test(EditBooking::class, ['record' => $record->id])
            ->fillForm(['status' => 'cancelada'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('cancelada', $record->fresh()->status);
        $this->assertNull($record->fresh()->google_event_id);
        $this->assertSame($originalJson, $record->fresh()->getRawOriginal('custom_fields'));
        $this->assertSame(self::fields(), $record->fresh()->custom_fields);
    }

    public static function bookingModes(): iterable
    {
        foreach (['request_only', 'date_only', 'time_slots'] as $mode) {
            yield $mode => [$mode];
        }
    }
}

class BookingInfolistPreview extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Booking $record;

    public function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema->record($this->record));
    }

    public function render(): string
    {
        return '<div>{{ $this->infolist }}</div>';
    }
}
