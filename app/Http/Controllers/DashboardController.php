<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\RsvpPersonResponse;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\AdminDashboardAnalytics;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(InvitationPublicationSnapshotBuilder $snapshots, AdminDashboardAnalytics $analytics): Response
    {
        $user = request()->user();
        if (! $user->isAdmin()) {
            return $this->customerDashboard($user);
        }

        // The operational dashboard gets its own bounded, aggregate payload from
        // AdminDashboardAnalytics. Do not hydrate the entire event catalogue here.
        if ($user->isAdmin()) {
            return Inertia::render('Dashboard', ['events' => [], 'isAdmin' => true, 'analytics' => $analytics->data()]);
        }

        $relations = ['customer', 'package', 'payments', 'publications', 'template:id,name,component_key'];
        $events = Event::with($relations)->latest()->get();
        $events->each(function (Event $event) use ($snapshots): void {
            $live = $event->publications->sortByDesc('version')->first();
            $dirty = $live && ! hash_equals($live->snapshot_hash, $snapshots->hash($event));
            $event->setAttribute('live_version', $live?->version);
            $event->setAttribute('unpublished_changes', (bool) $dirty);
            $event->setAttribute('invitation_status', $event->invitationStatus());
            $event->unsetRelation('publications');
        });
        return Inertia::render('Dashboard', ['events' => $events, 'isAdmin' => true, 'analytics' => $analytics->data()]);
    }

    private function customerDashboard($user): Response
    {
        $events = $user->managedEvents()
            ->select(['events.id', 'events.customer_id', 'events.event_package_id', 'events.template_id', 'events.title', 'events.main_date', 'events.status', 'events.guest_capacity'])
            ->with(['package:id,maximum_guests', 'template:id,name,component_key'])
            ->withExists('publications')
            ->withCount([
                'invitationParties as active_party_count' => fn ($query) => $query->where('is_active', true),
                'invitationParties as responded_party_count' => fn ($query) => $query->where('is_active', true)->whereHas('rsvp', fn ($rsvp) => $rsvp->whereNotNull('submitted_at')),
            ])
            ->withSum(['invitationParties as allocated_capacity' => fn ($query) => $query->where('is_active', true)], 'maximum_party_size')
            ->orderBy('events.main_date')
            ->orderBy('events.id')
            ->get();
        $attendees = RsvpPersonResponse::query()
            ->selectRaw('invitation_parties.event_id, COUNT(*) as confirmed_attendees')
            ->join('rsvps', 'rsvps.id', '=', 'rsvp_person_responses.rsvp_id')
            ->join('invitation_parties', 'invitation_parties.id', '=', 'rsvps.invitation_party_id')
            ->whereIn('invitation_parties.event_id', $events->pluck('id'))
            ->where('invitation_parties.is_active', true)
            ->whereNotNull('rsvps.submitted_at')
            ->where('rsvp_person_responses.is_attending', true)
            ->groupBy('invitation_parties.event_id')
            ->pluck('confirmed_attendees', 'invitation_parties.event_id');
        $customer = $user->customer()->withCount('events')->first();
        $invitations = $events->map(function (Event $event) use ($attendees): array {
            $status = $event->invitationStatus();
            $families = (int) $event->active_party_count;
            $responded = (int) $event->responded_party_count;

            return [
                'id' => $event->id,
                'title' => $event->title ?: 'Your invitation',
                'template_name' => $event->template?->name,
                'status' => $status,
                'date' => $event->main_date?->format('F j, Y'),
                'guest_capacity' => $event->effectiveGuestCapacity(),
                'allocated_capacity' => (int) ($event->allocated_capacity ?? 0),
                'families_invited' => $families,
                'responded_families' => $responded,
                'confirmed_attendees' => (int) ($attendees[$event->id] ?? 0),
                'response_rate' => $families ? (int) round($responded / $families * 100) : 0,
                'invitation_url' => in_array($status, ['live', 'disabled'], true) ? route('events.builder', $event) : route('events.setup', $event),
                'manage_url' => $status === 'archived' ? route('events.show', $event) : (in_array($status, ['live', 'disabled'], true) ? route('events.builder', $event) : route('events.setup', $event)),
                'preview_url' => route('events.invitation.preview', $event),
                'view_url' => $status === 'live' ? route('events.invitation.live-preview', $event) : null,
                'guests_url' => route('events.guests.index', $event),
                'rsvps_url' => route('events.rsvps.index', $event),
                'meals_url' => route('events.meals.index', $event),
                'schedule_url' => route('events.activities.index', $event),
                'next_action' => $status === 'archived'
                    ? ['title' => 'Your invitation is archived', 'description' => 'You can review this invitation, but it can no longer be changed.', 'label' => 'View Invitation', 'url' => route('events.show', $event)]
                    : ($status === 'disabled'
                    ? ['title' => 'Your invitation is disabled', 'description' => 'An Admin has temporarily made the public invitation unavailable.', 'label' => 'Edit Invitation', 'url' => route('events.builder', $event)]
                    : ($status === 'setup'
                    ? ['title' => 'Complete your invitation', 'description' => 'Your invitation is waiting for a few final details.', 'label' => 'Continue Setup', 'url' => route('events.setup', $event)]
                    : ($families === 0
                        ? ['title' => 'Add your first family', 'description' => 'Start inviting guests when you are ready.', 'label' => 'Manage Families & Guests', 'url' => route('events.guests.index', $event)]
                        : ($responded === 0
                            ? ['title' => 'Track guest responses', 'description' => 'Responses will appear here after your guests reply.', 'label' => 'View RSVP Responses', 'url' => route('events.rsvps.index', $event)]
                            : ['title' => 'Keep your invitation up to date', 'description' => 'Review your invitation and guest responses as plans come together.', 'label' => 'Edit Invitation', 'url' => route('events.builder', $event)])))),
            ];
        })->values();

        return Inertia::render('CustomerDashboard', [
            'invitations' => $invitations,
            'allowance' => $customer?->allowanceSummary() ?? ['allowed_events' => 0, 'used_events' => 0, 'remaining_events' => 0, 'can_create_event' => false],
            'completion' => request()->session()->get('invitation_completed'),
        ]);
    }
}
