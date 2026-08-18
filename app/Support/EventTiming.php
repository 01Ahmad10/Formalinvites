<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class EventTiming
{
    public static function deriveEndDate(array $data, string $timezone): array
    {
        $hasEndTime = ! empty($data['end_time']);

        if (! $hasEndTime) {
            $data['end_date'] = null;

            return $data;
        }

        if (empty($data['main_date']) || empty($data['start_time'])) {
            throw ValidationException::withMessages(['end_time' => 'Choose the event date and start time before adding an end time.']);
        }

        $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data['main_date']} {$data['start_time']}", $timezone);
        $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data['main_date']} {$data['end_time']}", $timezone);
        if ($end->equalTo($start)) {
            throw ValidationException::withMessages(['end_time' => 'End time cannot be the same as the start time.']);
        }

        $data['end_date'] = $end->greaterThan($start)
            ? $start->format('Y-m-d')
            : $start->addDay()->format('Y-m-d');

        return $data;
    }

    public static function date(?string $value, string $timezone): ?string
    {
        return $value ? CarbonImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10), $timezone)->format('F j, Y') : null;
    }

    public static function time(?string $value, string $timezone): ?string
    {
        if (! $value) return null;

        return CarbonImmutable::createFromFormat('!H:i:s', strlen($value) === 5 ? "{$value}:00" : $value, $timezone)->format('g:i A');
    }
}
