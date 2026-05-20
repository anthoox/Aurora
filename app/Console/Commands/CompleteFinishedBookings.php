<?php

namespace App\Console\Commands;

use App\Models\Booking;
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

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => 'realizada',
            ]);
        }

        $this->info("Reservas actualizadas: {$bookings->count()}");

        return self::SUCCESS;
    }
}