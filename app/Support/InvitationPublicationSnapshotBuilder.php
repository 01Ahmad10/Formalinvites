<?php

namespace App\Support;

use App\Models\Event;
use Carbon\CarbonImmutable;

class InvitationPublicationSnapshotBuilder
{
    public function build(Event $event): array
    {
        $event->loadMissing(['template', 'templateSetting']);

        $timezone = $event->event_timezone ?: config('app.timezone');
        $template = $event->template;
        $activities = $event->activities()->where('is_active', true)->orderBy('display_order')->orderBy('starts_at')->orderBy('id')->get();
        $meals = $event->mealOptions()->where('is_active', true)->orderBy('display_order')->orderBy('id')->get();

        return [
            'event' => [
                'title' => $event->title,
                'event_type' => $event->event_type,
                'host_name' => $event->host_name,
                'second_host_name' => $event->second_host_name,
                'description' => $event->description,
                'main_date' => $this->date($event->getRawOriginal('main_date')),
                'start_time' => $this->time($event->start_time),
                'end_time' => $this->time($event->end_time),
                'event_timezone' => $timezone,
                'venue' => $event->venue,
                'address' => $event->address,
                'location_url' => $event->location_url,
                'rsvp_deadline' => $this->date($event->getRawOriginal('rsvp_deadline')),
                'dress_code' => $event->dress_code,
                'parking_information' => $event->parking_information,
                'transportation_information' => $event->transportation_information,
                'accommodation_information' => $event->accommodation_information,
                'guest_information' => $event->guest_information,
            ],
            'template' => $template ? [
                'name' => $template->name,
                'component_key' => $template->component_key,
            ] : null,
            'settings' => InvitationTemplateSettings::resolve($template?->default_settings, $event->templateSetting?->settings),
            'activities' => $activities->map(fn ($activity) => [
                'title' => $activity->title,
                'activity_type' => $activity->activity_type,
                'description' => $activity->description,
                'starts_at' => CarbonImmutable::instance($activity->starts_at)->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
                'ends_at' => $activity->ends_at ? CarbonImmutable::instance($activity->ends_at)->utc()->format('Y-m-d\\TH:i:s.u\\Z') : null,
                'venue' => $activity->venue,
                'address' => $activity->address,
                'location_url' => $activity->location_url,
                'location_notes' => $activity->location_notes,
                'display_order' => (int) $activity->display_order,
            ])->values()->all(),
            'rsvp' => [
                'deadline' => $this->date($event->getRawOriginal('rsvp_deadline')),
                'timezone' => $timezone,
                // source_id is never sent to the browser. It lets a published
                // meal remain selectable even if the working copy deactivates it.
                'meals' => $meals->map(fn ($meal) => [
                    'source_id' => $meal->id,
                    'name' => $meal->name,
                    'description' => $meal->description,
                    'display_order' => (int) $meal->display_order,
                ])->values()->all(),
            ],
        ];
    }

    public function hash(Event $event): string
    {
        return $this->hashSnapshot($this->build($event));
    }

    public function hashSnapshot(array $snapshot): string
    {
        return hash('sha256', json_encode($this->canonicalize($snapshot), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) return $value;

        if (array_is_list($value)) return array_map(fn ($item) => $this->canonicalize($item), $value);

        ksort($value);

        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }

    private function date(?string $value): ?string
    {
        return $value ? substr($value, 0, 10) : null;
    }

    private function time(?string $value): ?string
    {
        if (! $value) return null;

        return strlen($value) === 5 ? "{$value}:00" : $value;
    }
}
