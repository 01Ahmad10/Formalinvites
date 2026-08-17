<?php

namespace App\Http\Controllers;

use App\Models\EventMealOption;
use App\Support\PublicRsvpPayload;
use App\Support\PublishedInvitationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicRsvpController extends Controller
{
    public function show(Request $request, string $token, PublicRsvpPayload $payload, PublishedInvitationResolver $published): Response
    {
        [$party, $publication] = $published->forToken($token);

        return Inertia::render('PublicRsvp', [
            'party' => $payload->party($party, $publication),
            'rsvp' => $payload->rsvp($party, $publication),
            'meals' => $payload->meals($party, $publication),
            'closed' => $payload->closed($party, $publication),
            'confirmation' => $request->session()->get('success'),
        ]);
    }

    public function submit(Request $request, string $token, PublicRsvpPayload $payload, PublishedInvitationResolver $published): RedirectResponse
    {
        [$party, $publication] = $published->forToken($token);

        if ($payload->closed($party, $publication)) {
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
        foreach ($data['members'] ?? [] as $index => $row) {
            $memberId = $payload->memberId($party, $row['id']);
            if (! $memberId) throw ValidationException::withMessages(['members' => 'An RSVP member does not belong to this invitation.']);
            $data['members'][$index]['id'] = $memberId;
            $mealId = $payload->mealId($party, $row['event_meal_option_id'] ?? null, $publication);
            if (($row['event_meal_option_id'] ?? null) && ! $mealId) throw ValidationException::withMessages(['members' => 'A selected meal option is unavailable for this published invitation.']);
            $data['members'][$index]['event_meal_option_id'] = $mealId;
        }
        foreach ($data['additional_guests'] ?? [] as $index => $row) {
            $mealId = $payload->mealId($party, $row['event_meal_option_id'] ?? null, $publication);
            if (($row['event_meal_option_id'] ?? null) && ! $mealId) throw ValidationException::withMessages(['members' => 'A selected meal option is unavailable for this published invitation.']);
            $data['additional_guests'][$index]['event_meal_option_id'] = $mealId;
        }

        if ($data['status'] === 'not_attending') {
            $rsvp->personResponses()->delete();
            $rsvp->update(['status' => 'not_attending', 'guest_message' => $data['guest_message'] ?? null, 'submitted_at' => $rsvp->submitted_at ?? now(), 'last_updated_at' => now()]);

            return $this->redirectAfterSubmission($request, $token)->with('success', 'Thank you for letting us know.');
        }

        $memberIds = collect($data['members'] ?? [])->pluck('id')->map(fn ($id) => (int) $id);
        $validMembers = $party->members()->whereIn('id', $memberIds)->get()->keyBy('id');
        if ($validMembers->count() !== $memberIds->unique()->count()) throw ValidationException::withMessages(['members' => 'An RSVP member does not belong to this invitation.']);

        $additional = $data['additional_guests'] ?? [];
        $additionalLimit = max($party->maximum_party_size - $party->members()->count(), 0);
        if (count($additional) > $additionalLimit) throw ValidationException::withMessages(['additional_guests' => "This invitation has only {$additionalLimit} additional guest slots."]);

        $attending = collect($data['members'] ?? [])->where('is_attending', true)->count() + count($additional);
        if ($attending > $party->maximum_party_size) throw ValidationException::withMessages(['members' => 'The RSVP exceeds this invitation party maximum size.']);

        $mealIds = collect([...collect($data['members'] ?? [])->pluck('event_meal_option_id')->filter(), ...collect($additional)->pluck('event_meal_option_id')->filter()])->unique();
        if ($mealIds->isNotEmpty() && EventMealOption::where('event_id', $party->event_id)->whereIn('id', $mealIds)->count() !== $mealIds->count()) {
            throw ValidationException::withMessages(['members' => 'A selected meal option is unavailable for this published invitation.']);
        }

        $rsvp->personResponses()->delete();
        foreach ($data['members'] ?? [] as $row) {
            $member = $validMembers[(int) $row['id']];
            $rsvp->personResponses()->create(['party_member_id' => $member->id, 'first_name' => $member->first_name, 'last_name' => $member->last_name, 'member_type' => $member->member_type, 'is_original_party_member' => true, 'is_attending' => $row['is_attending'], 'event_meal_option_id' => $row['is_attending'] ? ($row['event_meal_option_id'] ?? null) : null, 'dietary_note' => $row['is_attending'] ? ($row['dietary_note'] ?? null) : null]);
        }
        foreach ($additional as $row) $rsvp->personResponses()->create([...$row, 'is_original_party_member' => false, 'is_attending' => true]);
        $rsvp->update(['status' => 'attending', 'guest_message' => $data['guest_message'] ?? null, 'submitted_at' => $rsvp->submitted_at ?? now(), 'last_updated_at' => now()]);

        return $this->redirectAfterSubmission($request, $token)->with('success', 'Thank you. Your RSVP has been received.');
    }

    private function redirectAfterSubmission(Request $request, string $token): RedirectResponse
    {
        $invitationUrl = route('public.invitation.show', $token);
        $refererPath = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
        $invitationPath = parse_url($invitationUrl, PHP_URL_PATH);

        return $refererPath === $invitationPath ? to_route('public.invitation.show', $token) : to_route('public.rsvp.show', $token);
    }
}
