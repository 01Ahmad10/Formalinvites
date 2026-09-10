<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\EventPublicationService;
use App\Support\InvitationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventPublicationController extends Controller
{
    public function publish(Request $request, Event $event, EventPublicationService $publications): RedirectResponse
    {
        $this->manage($request, $event);

        DB::transaction(function () use ($event, $request, $publications): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            abort_if($event->isArchived(), 422, 'Archived Events cannot be published.');
            abort_if($event->isDisabled(), 422, 'Disabled invitations can only be enabled by an Admin.');
            $latest = $event->publications()->orderByDesc('version')->lockForUpdate()->first();
            $publication = $publications->activateLocked($event, $request->user());
            if ($latest && $publication->is($latest)) {
                throw ValidationException::withMessages(['workflow' => 'There are no unpublished invitation changes to publish.']);
            }
        });

        if (! $request->user()->isAdmin()) {
            return to_route('dashboard')->with('invitation_completed', [
                'event_id' => $event->id,
                'guests_url' => route('events.guests.index', $event),
                'view_url' => route('events.invitation.live-preview', $event),
            ]);
        }

        return back()->with('success', 'Invitation published successfully.');
    }

    public function disable(Request $request, Event $event): RedirectResponse
    {
        $this->admin($request);
        abort_unless($event->isLive() && $event->publications()->exists(), 422, 'Only Live invitations can be disabled.');
        $event->update(['status' => 'disabled']);

        return back()->with('success', 'The invitation is disabled. Public invitation and RSVP links are unavailable.');
    }

    public function enable(Request $request, Event $event): RedirectResponse
    {
        $this->admin($request);
        abort_unless($event->isDisabled() && $event->publications()->exists(), 422, 'Only disabled invitations with a publication can be enabled.');
        $event->update(['status' => 'published']);

        return back()->with('success', 'The invitation is Live again.');
    }

    public function archive(Request $request, Event $event): RedirectResponse
    {
        $this->admin($request);
        $event->update(['status' => 'archived']);

        return back()->with('success', 'The Event has been archived. Existing publication history was preserved.');
    }

    public function livePreview(Request $request, Event $event, InvitationPresenter $presenter): Response
    {
        $this->view($request, $event);
        $publication = $event->publications()->orderByDesc('version')->firstOrFail();

        return Inertia::render('Events/InvitationPreview', [
            'invitation' => $presenter->presentPublication($publication),
            'previewLabel' => "Live Version {$publication->version}",
        ]);
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function admin(Request $request): void { abort_unless($request->user()->isAdmin(), 403); }
}
