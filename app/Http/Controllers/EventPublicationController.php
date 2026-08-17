<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPublication;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\InvitationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventPublicationController extends Controller
{
    public function submit(Request $request, Event $event, InvitationPublicationSnapshotBuilder $snapshots): RedirectResponse
    {
        $this->manage($request, $event);
        abort_if($event->status === 'archived', 422, 'Archived Events cannot be submitted for review.');

        DB::transaction(function () use ($event, $snapshots): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            $event->update([
                'status' => 'submitted',
                'submitted_snapshot_hash' => $snapshots->hash($event),
                'submitted_at' => now(),
                'approved_snapshot_hash' => null,
                'approved_at' => null,
                'approved_by' => null,
                'review_note' => null,
            ]);
        });

        return back()->with('success', 'Working copy submitted for review.');
    }

    public function underReview(Request $request, Event $event): RedirectResponse
    {
        $this->admin($request);
        abort_unless($event->status === 'submitted' && $this->hasSubmittedSnapshot($event), 422, 'Submit this Event for review before marking it under review.');
        $event->update(['status' => 'under_review']);

        return back()->with('success', 'Event marked as under review.');
    }

    public function requestChanges(Request $request, Event $event): RedirectResponse
    {
        $this->admin($request);
        abort_unless(in_array($event->status, ['submitted', 'under_review'], true) && $this->hasSubmittedSnapshot($event), 422, 'Only a submitted Event can be returned for changes.');
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        $event->update([
            'status' => 'changes_requested',
            'review_note' => $data['review_note'],
            'approved_snapshot_hash' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', 'Changes requested from the Event team.');
    }

    public function approve(Request $request, Event $event, InvitationPublicationSnapshotBuilder $snapshots): RedirectResponse
    {
        $this->admin($request);

        DB::transaction(function () use ($event, $request, $snapshots): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if (! in_array($event->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['workflow' => 'Only a submitted Event can be approved.']);
            }
            $this->requireSubmittedSnapshot($event);
            $hash = $snapshots->hash($event);

            if (! hash_equals($event->submitted_snapshot_hash, $hash)) {
                throw ValidationException::withMessages(['workflow' => 'This Event changed after submission. Ask the Event team to submit the current working copy again.']);
            }

            $event->update([
                'status' => 'approved',
                'approved_snapshot_hash' => $hash,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'review_note' => null,
            ]);
        });

        return back()->with('success', 'The submitted working copy has been approved.');
    }

    public function publish(Request $request, Event $event, InvitationPublicationSnapshotBuilder $snapshots): RedirectResponse
    {
        $this->admin($request);

        DB::transaction(function () use ($event, $request, $snapshots): void {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($event->status !== 'approved' || ! $event->approved_snapshot_hash || ! $event->approved_at || ! $event->approved_by) {
                throw ValidationException::withMessages(['workflow' => 'Approve the current submitted working copy before publishing it.']);
            }

            $snapshot = $snapshots->build($event);
            $hash = $snapshots->hashSnapshot($snapshot);
            if (! hash_equals($event->approved_snapshot_hash, $hash)) {
                throw ValidationException::withMessages(['workflow' => 'This Event changed after approval. Submit and approve the current working copy before publishing.']);
            }

            $latestVersion = (int) (EventPublication::query()->where('event_id', $event->id)->orderByDesc('version')->lockForUpdate()->value('version') ?? 0);
            EventPublication::create([
                'event_id' => $event->id,
                'version' => $latestVersion + 1,
                'snapshot' => $snapshot,
                'snapshot_hash' => $hash,
                'published_by' => $request->user()->id,
                'published_at' => now(),
            ]);
            $event->update(['status' => 'published']);
        });

        return back()->with('success', 'A new live invitation version has been published.');
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

    private function requireSubmittedSnapshot(Event $event): void
    {
        if (! $this->hasSubmittedSnapshot($event)) {
            throw ValidationException::withMessages(['workflow' => 'The Event must be submitted for review before approval.']);
        }
    }

    private function hasSubmittedSnapshot(Event $event): bool
    {
        return filled($event->submitted_snapshot_hash) && $event->submitted_at !== null;
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function admin(Request $request): void { abort_unless($request->user()->isAdmin(), 403); }
}
