<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\InvitationParty;
use App\Models\PartyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GuestManagementController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->authorizeView($request, $event);
        $search = $request->string('search')->trim()->toString();
        $active = $request->string('active')->toString();
        $members = $request->string('members')->toString();
        $parties = $event->invitationParties()->withCount('members')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('primary_contact_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhereHas('members', fn ($member) => $member->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
            ->when(in_array($active, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $active === 'active'))
            ->when($members === 'with', fn ($query) => $query->has('members'))
            ->when($members === 'without', fn ($query) => $query->doesntHave('members'))
            ->orderBy('name')->get();
        $allParties = $event->invitationParties()->withCount('members')->get();
        $capacity = $event->package?->maximum_guests;
        $allocated = $event->allocatedGuestCapacity();

        return Inertia::render('Events/Guests/Index', ['event' => $event->load('package'), 'parties' => $parties, 'summary' => ['package_capacity' => $capacity, 'allocated_capacity' => $allocated, 'remaining_capacity' => $capacity === null ? null : max($capacity - $allocated, 0), 'party_count' => $allParties->count(), 'member_count' => $allParties->sum('members_count')], 'filters' => ['search' => $search, 'active' => $active, 'members' => $members], 'canManage' => $request->user()->can('update', $event)]);
    }

    public function storeParty(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeManage($request, $event);
        $data = $this->validatedParty($request);

        DB::transaction(function () use ($event, $data, $request): void {
            $event = Event::query()->lockForUpdate()->with('package')->findOrFail($event->id);
            $this->assertCapacity($event, (int) $data['maximum_party_size']);
            $event->invitationParties()->create([...$data, 'is_active' => true, 'created_by' => $request->user()->id]);
        });

        return back()->with('success', 'Invitation party created.');
    }

    public function showParty(Request $request, Event $event, InvitationParty $party): Response
    {
        $this->authorizeView($request, $event);
        $party = $this->partyForEvent($event, $party)->load(['members', 'createdBy:id,name', 'event.package', 'rsvp.personResponses.mealOption']);
        $additionalGuests = ($party->rsvp?->personResponses ?? collect())->where('is_original_party_member', false)->map(fn ($person) => [
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'member_type' => $person->member_type,
            'is_attending' => $person->is_attending,
            'meal' => $person->mealOption?->name,
            'dietary_note' => $person->dietary_note,
        ])->values();

        return Inertia::render('Events/Guests/Show', ['event' => $event, 'party' => $party, 'rsvpAdditionalGuests' => $additionalGuests, 'partyDates' => ['created_at' => $this->formatDateTime($party->created_at), 'updated_at' => $this->formatDateTime($party->updated_at)], 'rsvpUrl' => route('public.rsvp.show', $party->rsvp_token), 'canManage' => $request->user()->can('update', $event), 'memberTypes' => PartyMember::TYPES]);
    }

    public function updateParty(Request $request, Event $event, InvitationParty $party): RedirectResponse
    {
        $this->authorizeManage($request, $event);
        $party = $this->partyForEvent($event, $party);
        $data = $this->validatedParty($request);

        DB::transaction(function () use ($event, $party, $data): void {
            $event = Event::query()->lockForUpdate()->with('package')->findOrFail($event->id);
            $party = InvitationParty::query()->lockForUpdate()->findOrFail($party->id);
            if ($party->members()->count() > (int) $data['maximum_party_size']) throw ValidationException::withMessages(['maximum_party_size' => 'Maximum party size cannot be lower than the number of listed members.']);
            if ($party->is_active) $this->assertCapacity($event, (int) $data['maximum_party_size'], $party->id);
            $party->update($data);
        });

        return to_route('events.guests.show', [$event, $party])->with('success', 'Invitation party updated.');
    }

    public function setPartyActive(Request $request, Event $event, InvitationParty $party): RedirectResponse
    {
        $this->authorizeManage($request, $event);
        $party = $this->partyForEvent($event, $party);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        DB::transaction(function () use ($event, $party, $data): void {
            $event = Event::query()->lockForUpdate()->with('package')->findOrFail($event->id);
            $party = InvitationParty::query()->lockForUpdate()->findOrFail($party->id);
            if ($data['is_active']) $this->assertCapacity($event, $party->maximum_party_size, $party->id);
            $party->update(['is_active' => $data['is_active']]);
        });

        return back()->with('success', $data['is_active'] ? 'Invitation party activated.' : 'Invitation party deactivated.');
    }

    public function storeMember(Request $request, Event $event, InvitationParty $party): RedirectResponse
    {
        $this->authorizeManage($request, $event);
        $party = $this->partyForEvent($event, $party);
        $data = $this->validatedMember($request);
        if ($party->members()->count() >= $party->maximum_party_size) throw ValidationException::withMessages(['first_name' => 'This party already has the maximum number of listed members.']);
        $party->members()->create($data);

        return back()->with('success', 'Party member added.');
    }

    public function updateMember(Request $request, Event $event, InvitationParty $party, PartyMember $member): RedirectResponse
    {
        $this->authorizeManage($request, $event);
        $party = $this->partyForEvent($event, $party);
        $member = $this->memberForParty($party, $member);
        $member->update($this->validatedMember($request));

        return back()->with('success', 'Party member updated.');
    }

    public function destroyMember(Request $request, Event $event, InvitationParty $party, PartyMember $member): RedirectResponse
    {
        $this->authorizeManage($request, $event);
        $party = $this->partyForEvent($event, $party);
        $this->memberForParty($party, $member)->delete();

        return back()->with('success', 'Party member removed.');
    }

    private function authorizeView(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function authorizeManage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function partyForEvent(Event $event, InvitationParty $party): InvitationParty { abort_unless($party->event_id === $event->id, 404); return $party; }
    private function memberForParty(InvitationParty $party, PartyMember $member): PartyMember { abort_unless($member->invitation_party_id === $party->id, 404); return $member; }
    private function formatDateTime(?\DateTimeInterface $dateTime): ?string { return $dateTime ? \Carbon\CarbonImmutable::instance($dateTime)->setTimezone(config('app.timezone'))->format('F j, Y \\a\\t g:i A') : null; }

    private function assertCapacity(Event $event, int $maximumPartySize, ?int $exceptPartyId = null): void
    {
        if (! $event->package) return;
        $remaining = $event->package->maximum_guests - $event->allocatedGuestCapacity($exceptPartyId);
        if ($maximumPartySize > $remaining) throw ValidationException::withMessages(['maximum_party_size' => "This invitation would exceed the event's guest capacity. Only ".max($remaining, 0).' guest spots remain.']);
    }

    private function validatedParty(Request $request): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'primary_contact_name' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:50'], 'maximum_party_size' => ['required', 'integer', 'min:1'], 'table_name' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string']]);
    }

    private function validatedMember(Request $request): array
    {
        return $request->validate(['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['nullable', 'string', 'max:100'], 'member_type' => ['required', Rule::in(PartyMember::TYPES)], 'notes' => ['nullable', 'string']]);
    }
}
