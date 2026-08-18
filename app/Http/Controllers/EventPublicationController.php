<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPublication;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\InvitationPresenter;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventPublicationController extends Controller
{
    public function publish(Request $request, Event $event, InvitationPublicationSnapshotBuilder $snapshots): RedirectResponse
    {
        $this->manage($request, $event);

        DB::transaction(function () use ($event, $request, $snapshots): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            abort_if($event->status === 'archived', 422, 'Archived Events cannot be published.');
            $this->ensureReady($event);

            $snapshot = $snapshots->build($event);
            $hash = $snapshots->hashSnapshot($snapshot);
            $latest = EventPublication::query()
                ->where('event_id', $event->id)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            if ($latest && hash_equals($latest->snapshot_hash, $hash)) {
                throw ValidationException::withMessages(['workflow' => 'There are no unpublished invitation changes to publish.']);
            }

            EventPublication::create([
                'event_id' => $event->id,
                'version' => ($latest?->version ?? 0) + 1,
                'snapshot' => $snapshot,
                'snapshot_hash' => $hash,
                'published_by' => $request->user()->id,
                'published_at' => now(),
            ]);
            $event->update(['status' => 'published']);
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

    private function ensureReady(Event $event): void
    {
        $event->loadMissing('template');
        $errors = [];

        if (! filled($event->title)) $errors['title'] = 'Complete Event Details (Step 1) before publishing.';
        if (! filled($event->event_type)) $errors['event_type'] = 'Complete Event Details (Step 1) before publishing.';
        if (! filled($event->host_name)) $errors['host_name'] = 'Complete Event Details (Step 1) before publishing.';
        if (! $event->main_date) $errors['main_date'] = 'Complete Date & Location (Step 2) before publishing.';
        if (! filled($event->start_time)) $errors['start_time'] = 'Complete Date & Location (Step 2) before publishing.';
        if (! filled($event->event_timezone) || ! in_array($event->event_timezone, DateTimeZone::listIdentifiers(), true)) $errors['event_timezone'] = 'Choose a valid Event timezone in Date & Location (Step 2).';
        if (! $event->template) $errors['template_id'] = 'Choose a trusted invitation Template in Choose Design (Step 4).';

        if ($errors) throw ValidationException::withMessages($errors);
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function admin(Request $request): void { abort_unless($request->user()->isAdmin(), 403); }
}
