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
            abort_if($event->status === 'archived', 422, 'Archived Events cannot be published.');
            $latest = $event->publications()->orderByDesc('version')->lockForUpdate()->first();
            $publication = $publications->activateLocked($event, $request->user());
            if ($latest && $publication->is($latest)) {
                throw ValidationException::withMessages(['workflow' => 'There are no unpublished invitation changes to publish.']);
            }
        });

        return back()->with('success', 'Invitation published successfully.');
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
