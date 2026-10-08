<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingScheduler;
use Illuminate\Console\Command;

class CompleteFinishedBookings extends Command
{
    protected $signature = 'aurora:complete-bookings';

    protected $description = 'Marca como realizadas las reservas confirmadas que ya han finalizado';

    public function handle(): int
    {
        $bookings = Booking::query()
            ->where('status', 'confirmada')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        $completed = 0;
        foreach ($bookings as $booking) {
            $completed += (int) app(BookingScheduler::class)->completeIfFinished($booking);
        }

        $this->info("Reservas actualizadas: {$completed}");

        return self::SUCCESS;
    }
}
