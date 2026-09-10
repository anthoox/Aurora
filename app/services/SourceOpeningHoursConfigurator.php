<?php

namespace App\Services;

use App\Models\Source;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SourceOpeningHoursConfigurator
{
    /**
     * @param  array<int, int|string>  $days
     * @param  array<int, array{opens_at: string, closes_at: string}>  $ranges
     */
    public function configure(
        Source $source,
        array $days,
        array $ranges,
        int|string $slotIntervalMinutes,
        string $mode,
    ): void {
        $days = array_map('intval', $days);
        $slotIntervalMinutes = (int) $slotIntervalMinutes;

        Validator::make([
            'days' => $days,
            'ranges' => $ranges,
            'slot_interval_minutes' => $slotIntervalMinutes,
            'mode' => $mode,
        ], [
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'ranges' => ['required', 'array', 'min:1'],
            'ranges.*.opens_at' => ['required', 'date_format:H:i'],
            'ranges.*.closes_at' => ['required', 'date_format:H:i', 'after:ranges.*.opens_at'],
            'slot_interval_minutes' => ['required', 'integer', Rule::in(config('bookings.allowed_slot_intervals'))],
            'mode' => ['required', Rule::in(['add', 'replace'])],
        ], [
            'days.required' => 'Selecciona al menos un día de la semana.',
            'days.min' => 'Selecciona al menos un día de la semana.',
            'ranges.required' => 'Añade al menos un rango horario.',
            'ranges.min' => 'Añade al menos un rango horario.',
            'ranges.*.closes_at.after' => 'La hora de cierre debe ser posterior a la hora de apertura.',
            'slot_interval_minutes.in' => 'Selecciona un intervalo entre reservas válido.',
        ])->validate();

        $this->ensureRangesDoNotOverlap($ranges);

        DB::transaction(function () use ($source, $days, $ranges, $slotIntervalMinutes, $mode): void {
            $lockedSource = Source::query()
                ->whereKey($source->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSource->update([
                'slot_interval_minutes' => $slotIntervalMinutes,
            ]);

            if ($mode === 'replace') {
                $lockedSource->openingHours()
                    ->whereIn('day_of_week', $days)
                    ->delete();
            }

            foreach ($days as $day) {
                foreach ($ranges as $range) {
                    $lockedSource->openingHours()->create([
                        'day_of_week' => $day,
                        'opens_at' => $range['opens_at'],
                        'closes_at' => $range['closes_at'],
                        'is_active' => true,
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, array{opens_at: string, closes_at: string}>  $ranges
     */
    private function ensureRangesDoNotOverlap(array $ranges): void
    {
        usort($ranges, fn (array $first, array $second): int => $first['opens_at'] <=> $second['opens_at']);

        for ($index = 1; $index < count($ranges); $index++) {
            if ($ranges[$index]['opens_at'] < $ranges[$index - 1]['closes_at']) {
                throw ValidationException::withMessages([
                    'ranges' => 'Los rangos horarios no pueden solaparse entre sí.',
                ]);
            }
        }
    }
}
