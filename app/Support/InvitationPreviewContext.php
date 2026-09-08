<?php

namespace App\Support;

use App\Models\InvitationParty;

class InvitationPreviewContext
{
    public static function party(InvitationParty $party): array
    {
        $party->loadMissing(['members', 'rsvp.personResponses.mealOption']);
        $rsvp = $party->rsvp;

        return [
            'id' => $party->id, 'name' => $party->name,
            'maximum_attendees' => $party->maximum_party_size,
            'maximum_party_size' => $party->maximum_party_size,
            'listed_member_count' => $party->members->count(),
            'members' => $party->members->map(fn ($member) => $member->only(['id', 'first_name', 'last_name', 'member_type']))->values(),
            'rsvp' => $rsvp ? [
                'status' => $rsvp->status, 'guest_message' => $rsvp->guest_message,
                'submitted_at' => $rsvp->submitted_at?->toIso8601String(), 'last_updated_at' => $rsvp->last_updated_at?->toIso8601String(),
                'person_responses' => $rsvp->personResponses->map(fn ($person) => [
                    ...$person->only(['party_member_id', 'first_name', 'last_name', 'member_type', 'is_original_party_member', 'is_attending', 'event_meal_option_id', 'dietary_note']),
                    'meal_option' => $person->mealOption ? ['name' => $person->mealOption->name] : null,
                ])->values(),
            ] : null,
        ];
    }
}
