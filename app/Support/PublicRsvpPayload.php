<?php

namespace App\Support;

use App\Models\InvitationParty;
use Carbon\CarbonImmutable;

class PublicRsvpPayload
{
    public function party(InvitationParty $party): array
    {
        $party->loadMissing(['event', 'members']);
        $event = $party->event;
        $timezone = $event->event_timezone ?: config('app.timezone');

        return [
            'name' => $party->name,
            'maximum_party_size' => (int) $party->maximum_party_size,
            'listed_member_count' => $party->members->count(),
            'event' => [
                'title' => $event->title,
                'event_type' => str($event->event_type)->headline()->toString(),
                'host_name' => $event->host_name,
                'second_host_name' => $event->second_host_name,
                'main_date' => $this->date($event->getRawOriginal('main_date'), $timezone),
                'start_time' => $this->time($event->start_time, $timezone),
                'end_time' => $this->time($event->end_time, $timezone),
                'venue' => $event->venue,
                'address' => $event->address,
                'location_url' => $event->location_url,
                'rsvp_deadline' => $event->rsvpDeadlineAt()?->format('F j, Y \a\t g:i A')." ({$timezone})",
                'schedule' => $event->activities()->where('is_active', true)->orderBy('display_order')->orderBy('starts_at')->get()->map(function ($activity) use ($timezone) {
                    $start = $activity->starts_at->setTimezone($timezone);
                    $end = $activity->ends_at?->setTimezone($timezone);

                    return ['title' => $activity->title, 'activity_type' => $activity->activity_type, 'description' => $activity->description, 'date' => $start->format('F j, Y'), 'start_time' => $start->format('g:i A'), 'end_date' => $end?->format('F j, Y'), 'end_time' => $end?->format('g:i A'), 'venue' => $activity->venue, 'address' => $activity->address, 'location_url' => $activity->location_url, 'location_notes' => $activity->location_notes];
                })->values(),
                'guest_information' => ['dress_code' => $event->dress_code, 'parking_information' => $event->parking_information, 'transportation_information' => $event->transportation_information, 'accommodation_information' => $event->accommodation_information, 'additional_information' => $event->guest_information],
            ],
            'members' => $party->members->map(fn ($member) => ['id' => $member->id, 'first_name' => $member->first_name, 'last_name' => $member->last_name, 'member_type' => $member->member_type])->values(),
        ];
    }

    public function rsvp(InvitationParty $party): ?array
    {
        $rsvp = $party->rsvp()->with('personResponses.mealOption')->first();
        if (! $rsvp) return null;

        return ['status' => $rsvp->status, 'guest_message' => $rsvp->guest_message, 'submitted_at' => $this->dateTime($rsvp->submitted_at), 'last_updated_at' => $this->dateTime($rsvp->last_updated_at), 'person_responses' => $rsvp->personResponses->map(fn ($person) => ['party_member_id' => $person->party_member_id, 'first_name' => $person->first_name, 'last_name' => $person->last_name, 'member_type' => $person->member_type, 'is_original_party_member' => $person->is_original_party_member, 'is_attending' => $person->is_attending, 'event_meal_option_id' => $person->event_meal_option_id, 'dietary_note' => $person->dietary_note, 'meal_option' => $person->mealOption ? ['name' => $person->mealOption->name] : null])->values()];
    }

    public function meals(InvitationParty $party): array
    {
        return $party->event->mealOptions()->where('is_active', true)->orderBy('display_order')->get()->map(fn ($meal) => ['id' => $meal->id, 'name' => $meal->name])->values()->all();
    }

    public function invitationParty(InvitationParty $party): array
    {
        $payload = $this->party($party);
        $payload['members'] = $party->members->map(fn ($member) => ['id' => $this->memberKey($party, $member->id), 'first_name' => $member->first_name, 'last_name' => $member->last_name, 'member_type' => $member->member_type])->values();

        return $payload;
    }

    public function invitationRsvp(InvitationParty $party): ?array
    {
        $payload = $this->rsvp($party);
        if (! $payload) return null;

        $payload['person_responses'] = collect($payload['person_responses'])->map(function (array $person) use ($party) {
            $person['party_member_id'] = $person['party_member_id'] ? $this->memberKey($party, $person['party_member_id']) : null;
            $person['event_meal_option_id'] = $person['event_meal_option_id'] ? $this->mealKey($party, $person['event_meal_option_id']) : null;

            return $person;
        })->values();

        return $payload;
    }

    public function invitationMeals(InvitationParty $party): array
    {
        return $party->event->mealOptions()->where('is_active', true)->orderBy('display_order')->get()->map(fn ($meal) => ['id' => $this->mealKey($party, $meal->id), 'name' => $meal->name])->values()->all();
    }

    public function memberId(InvitationParty $party, mixed $identifier): ?int
    {
        if (is_int($identifier) || (is_string($identifier) && ctype_digit($identifier))) return (int) $identifier;

        foreach ($party->members as $member) if (is_string($identifier) && hash_equals($this->memberKey($party, $member->id), $identifier)) return $member->id;

        return null;
    }

    public function mealId(InvitationParty $party, mixed $identifier): ?int
    {
        if ($identifier === null || $identifier === '') return null;
        if (is_int($identifier) || (is_string($identifier) && ctype_digit($identifier))) return (int) $identifier;

        foreach ($party->event->mealOptions()->where('is_active', true)->get() as $meal) if (is_string($identifier) && hash_equals($this->mealKey($party, $meal->id), $identifier)) return $meal->id;

        return null;
    }

    public function closed(InvitationParty $party): bool
    {
        return $party->event->isRsvpClosed();
    }

    private function date(?string $date, string $timezone): ?string { return $date ? CarbonImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10), $timezone)->format('l, F j, Y') : null; }
    private function time(?string $time, string $timezone): ?string { return $time ? CarbonImmutable::parse($time, $timezone)->format('g:i A') : null; }
    private function dateTime(?\DateTimeInterface $dateTime): ?string { return $dateTime ? CarbonImmutable::instance($dateTime)->setTimezone(config('app.timezone'))->format('F j, Y \a\t g:i A') : null; }
    private function memberKey(InvitationParty $party, int $memberId): string { return hash_hmac('sha256', "party-member:{$party->rsvp_token}:{$memberId}", config('app.key')); }
    private function mealKey(InvitationParty $party, int $mealId): string { return hash_hmac('sha256', "meal:{$party->rsvp_token}:{$mealId}", config('app.key')); }
}
