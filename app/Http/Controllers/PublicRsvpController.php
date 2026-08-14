<?php

namespace App\Http\Controllers;

use App\Models\EventMealOption;
use App\Models\InvitationParty;
use App\Support\PublicRsvpPayload;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicRsvpController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $party = $this->party($token);

        return Inertia::render('PublicRsvp', [
            'party' => $this->publicParty($party),
            'rsvp' => $this->publicRsvp($party),
            'meals' => $party->event->mealOptions()->where('is_active', true)->orderBy('display_order')->get()->map(fn ($meal) => ['id' => $meal->id, 'name' => $meal->name])->values(),
            'closed' => $this->closed($party),
            'confirmation' => $request->session()->get('success'),
        ]);
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $party = $this->party($token);

        if ($this->closed($party)) {
            throw ValidationException::withMessages(['status' => 'The RSVP deadline has passed. Please contact the host if you need assistance.']);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['attending', 'not_attending'])],
            'guest_message' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*.id' => ['required', 'distinct'],
            'members.*.is_attending' => ['required', 'boolean'],
            'members.*.event_meal_option_id' => ['nullable'],
            'members.*.dietary_note' => ['nullable', 'string'],
            'additional_guests' => ['nullable', 'array'],
            'additional_guests.*.first_name' => ['required', 'string', 'max:100'],
            'additional_guests.*.last_name' => ['nullable', 'string', 'max:100'],
            'additional_guests.*.member_type' => ['required', Rule::in(['adult', 'child'])],
            'additional_guests.*.event_meal_option_id' => ['nullable'],
            'additional_guests.*.dietary_note' => ['nullable', 'string'],
        ]);

        $rsvp = $party->rsvp()->firstOrCreate([], ['status' => 'pending']);

        $publicPayload = app(PublicRsvpPayload::class);
        foreach ($data['members'] ?? [] as $index => $row) {
            $memberId = $publicPayload->memberId($party, $row['id']);
            if (! $memberId) throw ValidationException::withMessages(['members' => 'An RSVP member does not belong to this invitation.']);
            $data['members'][$index]['id'] = $memberId;
            $mealId = $publicPayload->mealId($party, $row['event_meal_option_id'] ?? null);
            if (($row['event_meal_option_id'] ?? null) && ! $mealId) throw ValidationException::withMessages(['members' => 'A selected meal option is unavailable for this event.']);
            $data['members'][$index]['event_meal_option_id'] = $mealId;
        }
        foreach ($data['additional_guests'] ?? [] as $index => $row) {
            $mealId = $publicPayload->mealId($party, $row['event_meal_option_id'] ?? null);
            if (($row['event_meal_option_id'] ?? null) && ! $mealId) throw ValidationException::withMessages(['members' => 'A selected meal option is unavailable for this event.']);
            $data['additional_guests'][$index]['event_meal_option_id'] = $mealId;
        }

        if ($data['status'] === 'not_attending') {
            $rsvp->personResponses()->delete();
            $rsvp->update(['status' => 'not_attending', 'guest_message' => $data['guest_message'] ?? null, 'submitted_at' => $rsvp->submitted_at ?? now(), 'last_updated_at' => now()]);

            return $this->redirectAfterSubmission($request, $token)->with('success', 'Thank you for letting us know.');
        }

        $memberIds = collect($data['members'] ?? [])->pluck('id')->map(fn ($id) => (int) $id);
        $validMembers = $party->members()->whereIn('id', $memberIds)->get()->keyBy('id');
        if ($validMembers->count() !== $memberIds->unique()->count()) {
            throw ValidationException::withMessages(['members' => 'An RSVP member does not belong to this invitation.']);
        }

        $additional = $data['additional_guests'] ?? [];
        $additionalLimit = max($party->maximum_party_size - $party->members()->count(), 0);
        if (count($additional) > $additionalLimit) {
            throw ValidationException::withMessages(['additional_guests' => "This invitation has only {$additionalLimit} additional guest slots."]);
        }

        $attending = collect($data['members'] ?? [])->where('is_attending', true)->count() + count($additional);
        if ($attending > $party->maximum_party_size) {
            throw ValidationException::withMessages(['members' => 'The RSVP exceeds this invitation party maximum size.']);
        }

        $mealIds = collect([...collect($data['members'] ?? [])->pluck('event_meal_option_id')->filter(), ...collect($additional)->pluck('event_meal_option_id')->filter()])->unique();
        if ($mealIds->isNotEmpty() && EventMealOption::where('event_id', $party->event_id)->where('is_active', true)->whereIn('id', $mealIds)->count() !== $mealIds->count()) {
            throw ValidationException::withMessages(['members' => 'A selected meal option is unavailable for this event.']);
        }

        $rsvp->personResponses()->delete();
        foreach ($data['members'] ?? [] as $row) {
            $member = $validMembers[(int) $row['id']];
            $rsvp->personResponses()->create([
                'party_member_id' => $member->id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'member_type' => $member->member_type,
                'is_original_party_member' => true,
                'is_attending' => $row['is_attending'],
                'event_meal_option_id' => $row['is_attending'] ? ($row['event_meal_option_id'] ?? null) : null,
                'dietary_note' => $row['is_attending'] ? ($row['dietary_note'] ?? null) : null,
            ]);
        }
        foreach ($additional as $row) {
            $rsvp->personResponses()->create([...$row, 'is_original_party_member' => false, 'is_attending' => true]);
        }
        $rsvp->update(['status' => 'attending', 'guest_message' => $data['guest_message'] ?? null, 'submitted_at' => $rsvp->submitted_at ?? now(), 'last_updated_at' => now()]);

        return $this->redirectAfterSubmission($request, $token)->with('success', 'Thank you. Your RSVP has been received.');
    }

    private function party(string $token): InvitationParty
    {
        return InvitationParty::where('rsvp_token', $token)->with(['event', 'members'])->firstOrFail();
    }

    private function closed(InvitationParty $party): bool
    {
        return $party->event->isRsvpClosed();
    }

    private function redirectAfterSubmission(Request $request, string $token): RedirectResponse
    {
        $invitationUrl = route('public.invitation.show', $token);
        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
        $invitationPath = parse_url($invitationUrl, PHP_URL_PATH);

        return $refererPath === $invitationPath
            ? to_route('public.invitation.show', $token)
            : to_route('public.rsvp.show', $token);
    }

    private function publicParty(InvitationParty $party): array
    {
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

                    return [
                        'title' => $activity->title,
                        'activity_type' => $activity->activity_type,
                        'description' => $activity->description,
                        'date' => $start->format('F j, Y'),
                        'start_time' => $start->format('g:i A'),
                        'end_date' => $end?->format('F j, Y'),
                        'end_time' => $end?->format('g:i A'),
                        'venue' => $activity->venue,
                        'address' => $activity->address,
                        'location_url' => $activity->location_url,
                        'location_notes' => $activity->location_notes,
                    ];
                })->values(),
                'guest_information' => [
                    'dress_code' => $event->dress_code,
                    'parking_information' => $event->parking_information,
                    'transportation_information' => $event->transportation_information,
                    'accommodation_information' => $event->accommodation_information,
                    'additional_information' => $event->guest_information,
                ],
            ],
            'members' => $party->members->map(fn ($member) => ['id' => $member->id, 'first_name' => $member->first_name, 'last_name' => $member->last_name, 'member_type' => $member->member_type])->values(),
        ];
    }

    private function publicRsvp(InvitationParty $party): ?array
    {
        $rsvp = $party->rsvp()->with('personResponses.mealOption')->first();
        if (! $rsvp) return null;

        return ['status' => $rsvp->status, 'guest_message' => $rsvp->guest_message, 'submitted_at' => $this->dateTime($rsvp->submitted_at), 'last_updated_at' => $this->dateTime($rsvp->last_updated_at), 'person_responses' => $rsvp->personResponses->map(fn ($person) => ['party_member_id' => $person->party_member_id, 'first_name' => $person->first_name, 'last_name' => $person->last_name, 'member_type' => $person->member_type, 'is_original_party_member' => $person->is_original_party_member, 'is_attending' => $person->is_attending, 'event_meal_option_id' => $person->event_meal_option_id, 'dietary_note' => $person->dietary_note, 'meal_option' => $person->mealOption ? ['name' => $person->mealOption->name] : null])->values()];
    }

    private function date(?string $date, string $timezone): ?string
    {
        return $date ? CarbonImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10), $timezone)->format('l, F j, Y') : null;
    }

    private function time(?string $time, string $timezone): ?string
    {
        return $time ? CarbonImmutable::parse($time, $timezone)->format('g:i A') : null;
    }

    private function dateTime(?\DateTimeInterface $dateTime): ?string
    {
        return $dateTime ? CarbonImmutable::instance($dateTime)->setTimezone(config('app.timezone'))->format('F j, Y \a\t g:i A') : null;
    }
}
