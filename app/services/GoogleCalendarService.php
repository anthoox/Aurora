<?php

namespace App\Services;

use App\Models\Booking;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

class GoogleCalendarService
{
  private Calendar $calendar;

  private string $calendarId;

  public function __construct()
  {
    $client = new Client();

    $credentialsPath = base_path(config('services.google_calendar.credentials_path'));

    if (!file_exists($credentialsPath)) {
      dd('No existe el archivo: ' . $credentialsPath);
    }

    $client->setAuthConfig($credentialsPath);
    $client->addScope(Calendar::CALENDAR);

    $this->calendar = new Calendar($client);
    $this->calendarId = config('services.google_calendar.calendar_id');
    
  }

  public function createEventFromBooking(Booking $booking): string
  {
    $event = new Event([
      'summary' => $this->buildSummary($booking),
      'description' => $this->buildDescription($booking),
      'start' => new EventDateTime([
        'dateTime' => $booking->starts_at->toRfc3339String(),
        'timeZone' => config('app.timezone'),
      ]),
      'end' => new EventDateTime([
        'dateTime' => ($booking->ends_at ?? $booking->starts_at->copy()->addHour())->toRfc3339String(),
        'timeZone' => config('app.timezone'),
      ]),
    ]);

    $createdEvent = $this->calendar->events->insert(
      $this->calendarId,
      $event
    );

    return $createdEvent->getId();
  }

  private function buildSummary(Booking $booking): string
  {
    $serviceName = $booking->service?->name ?? 'Reserva';

    return "{$serviceName} - {$booking->customer->first_name}";
  }

  private function buildDescription(Booking $booking): string
  {
    return trim("
Reserva creada desde Aurora CRM.

Cliente: {$booking->customer->first_name} {$booking->customer->last_name}
Email: {$booking->customer->email}
Teléfono: {$booking->customer->phone}

Origen: {$booking->source?->name}
Servicio: {$booking->service?->name}

Notas:
{$booking->notes}
        ");
  }

  public function deleteEvent(string $eventId): void
  {
    $this->calendar->events->delete(
      $this->calendarId,
      $eventId
    );
  }

  public function updateEventFromBooking(Booking $booking): void
  {
    if (!$booking->google_event_id) {
      return;
    }

    $event = new Event([
      'summary' => $this->buildSummary($booking),
      'description' => $this->buildDescription($booking),
      'start' => new EventDateTime([
        'dateTime' => $booking->starts_at->toRfc3339String(),
        'timeZone' => config('app.timezone'),
      ]),
      'end' => new EventDateTime([
        'dateTime' => ($booking->ends_at ?? $booking->starts_at->copy()->addHour())->toRfc3339String(),
        'timeZone' => config('app.timezone'),
      ]),
    ]);

    $this->calendar->events->update(
      $this->calendarId,
      $booking->google_event_id,
      $event
    );

    
  }

  
}