<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Guava\Calendar\Filament\CalendarWidget;
use Illuminate\Support\HtmlString;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Guava\Calendar\ValueObjects\CalendarEvent;
class BookingsCalendar extends CalendarWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected string|HtmlString|bool|null $heading = 'Calendario de reservas';

    public function getEvents(FetchInfo $info): Collection|array
    {
        return Booking::query()
            ->whereNotNull('starts_at')
            ->whereIn('status', ['pendiente', 'confirmada', 'realizada'])
            ->get()
            ->map(function (Booking $booking) {
                return CalendarEvent::make($booking)
                    ->title($this->getEventTitle($booking))
                    ->start($booking->starts_at)
                    ->end($booking->ends_at ?? $booking->starts_at->copy()->addHour())
                    ->url(BookingResource::getUrl('view', ['record' => $booking]));
            });
    }

    private function getEventTitle(Booking $booking): string
    {
        $service = $booking->service?->name ?? 'Reserva';
        $customer = $booking->customer?->first_name ?? 'Cliente';

        return "{$service} - {$customer}";
    }
}