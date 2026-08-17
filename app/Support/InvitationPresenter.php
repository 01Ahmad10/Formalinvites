<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use Carbon\CarbonImmutable;

class InvitationPresenter
{
    public function present(Event $event, ?InvitationParty $party = null): array
    {
        $timezone = $event->event_timezone ?: config('app.timezone');
        $event->loadMissing(['template', 'templateSetting', 'activities' => fn ($query) => $query->where('is_active', true)->orderBy('display_order')->orderBy('starts_at')]);
        $template = $event->template;

        return [
            'template' => $template ? ['name' => $template->name, 'component_key' => $template->component_key, 'is_active' => $template->is_active] : null,
            'experience' => $this->experience($template?->component_key),
            'settings' => InvitationTemplateSettings::resolve($template?->default_settings, $event->templateSetting?->settings),
            'party_name' => $party?->name,
            'event' => [
                'title' => $event->title,
                'event_type' => str($event->event_type)->headline()->toString(),
                'host_name' => $event->host_name,
                'second_host_name' => $event->second_host_name,
                'main_date' => $event->getRawOriginal('main_date') ? CarbonImmutable::createFromFormat('!Y-m-d', substr($event->getRawOriginal('main_date'), 0, 10), $timezone)->format('l, F j, Y') : null,
                'start_time' => $this->formatEventTime($event->start_time, $timezone),
                'end_time' => $this->formatEventTime($event->end_time, $timezone),
                'timezone' => $timezone,
                'venue' => $event->venue,
                'address' => $event->address,
                'location_url' => $event->location_url,
                'dress_code' => $event->dress_code,
                'parking_information' => $event->parking_information,
                'transportation_information' => $event->transportation_information,
                'accommodation_information' => $event->accommodation_information,
                'guest_information' => $event->guest_information,
                'rsvp_deadline' => $event->rsvpDeadlineAt()?->format('F j, Y \a\t g:i A')." ({$timezone})",
                'rsvp_open' => ! $event->isRsvpClosed(),
                'activities' => $event->activities->map(function ($activity) use ($timezone) {
                    $start = $activity->starts_at->setTimezone($timezone);
                    $end = $activity->ends_at?->setTimezone($timezone);

                    return ['title' => $activity->title, 'activity_type' => $activity->activity_type, 'description' => $activity->description, 'date' => $start->format('F j, Y'), 'start_time' => $start->format('g:i A'), 'end_date' => $end?->format('F j, Y'), 'end_time' => $end?->format('g:i A'), 'venue' => $activity->venue, 'address' => $activity->address, 'location_url' => $activity->location_url, 'location_notes' => $activity->location_notes];
                })->values(),
            ],
        ];
    }

    public function presentPublication(EventPublication $publication, ?InvitationParty $party = null): array
    {
        $snapshot = $publication->snapshot;
        $event = $snapshot['event'];
        $timezone = $event['event_timezone'] ?: config('app.timezone');

        return [
            'template' => $snapshot['template'],
            'experience' => $this->experience($snapshot['template']['component_key'] ?? null),
            'settings' => $snapshot['settings'],
            'party_name' => $party?->name,
            'event' => [
                'title' => $event['title'],
                'event_type' => str($event['event_type'])->headline()->toString(),
                'host_name' => $event['host_name'],
                'second_host_name' => $event['second_host_name'],
                'main_date' => $this->date($event['main_date'], $timezone),
                'start_time' => $this->formatEventTime($event['start_time'], $timezone),
                'end_time' => $this->formatEventTime($event['end_time'], $timezone),
                'timezone' => $timezone,
                'venue' => $event['venue'],
                'address' => $event['address'],
                'location_url' => $event['location_url'],
                'dress_code' => $event['dress_code'],
                'parking_information' => $event['parking_information'],
                'transportation_information' => $event['transportation_information'],
                'accommodation_information' => $event['accommodation_information'],
                'guest_information' => $event['guest_information'],
                'rsvp_deadline' => $this->deadline($snapshot['rsvp']['deadline'] ?? null, $timezone),
                'rsvp_open' => ! $this->closed($snapshot['rsvp']['deadline'] ?? null, $timezone),
                'activities' => collect($snapshot['activities'] ?? [])->map(function (array $activity) use ($timezone) {
                    $start = CarbonImmutable::parse($activity['starts_at'])->setTimezone($timezone);
                    $end = ! empty($activity['ends_at']) ? CarbonImmutable::parse($activity['ends_at'])->setTimezone($timezone) : null;

                    return ['title' => $activity['title'], 'activity_type' => $activity['activity_type'], 'description' => $activity['description'], 'date' => $start->format('F j, Y'), 'start_time' => $start->format('g:i A'), 'end_date' => $end?->format('F j, Y'), 'end_time' => $end?->format('g:i A'), 'venue' => $activity['venue'], 'address' => $activity['address'], 'location_url' => $activity['location_url'], 'location_notes' => $activity['location_notes']];
                })->values(),
            ],
        ];
    }

    private function formatEventTime(?string $time, string $timezone): ?string
    {
        if (! $time) {
            return null;
        }

        $normalizedTime = strlen($time) === 5 ? "{$time}:00" : $time;

        return CarbonImmutable::createFromFormat('!H:i:s', $normalizedTime, $timezone)->format('g:i A');
    }

    private function date(?string $date, string $timezone): ?string
    {
        return $date ? CarbonImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10), $timezone)->format('l, F j, Y') : null;
    }

    private function deadline(?string $date, string $timezone): ?string
    {
        return $date ? CarbonImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10), $timezone)->endOfDay()->format('F j, Y \\a\\t g:i A')." ({$timezone})" : null;
    }

    private function closed(?string $date, string $timezone): bool
    {
        return $date !== null && CarbonImmutable::now($timezone)->greaterThanOrEqualTo(CarbonImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10), $timezone)->endOfDay());
    }

    private function experience(?string $componentKey): array
    {
        // These are trusted component-owned hooks. Stage 6A intentionally ships
        // without media assets, so both safely fall back to the visual intro.
        return match ($componentKey) {
            'romantic-floral' => ['intro_video' => null, 'audio' => null],
            'editorial-luxury' => ['intro_video' => null, 'audio' => null],
            'modern-cinematic' => ['intro_video' => null, 'audio' => null],
            default => ['intro_video' => null, 'audio' => null],
        };
    }
}
