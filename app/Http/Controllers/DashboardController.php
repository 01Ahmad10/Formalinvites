<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\InvitationPublicationSnapshotBuilder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(InvitationPublicationSnapshotBuilder $snapshots): Response
    {
        $user = request()->user();
        $relations = ['customer', 'package', 'payments', 'publications'];
        $events = $user->isAdmin() || $user->isSupport() ? Event::with($relations)->latest()->get() : $user->managedEvents()->with($relations)->latest()->get();
        $events->each(function (Event $event) use ($snapshots): void {
            $live = $event->publications->sortByDesc('version')->first();
            $dirty = $live && ! hash_equals($live->snapshot_hash, $snapshots->hash($event));
            $event->setAttribute('live_version', $live?->version);
            $event->setAttribute('unpublished_changes', (bool) $dirty);
            $event->setAttribute('invitation_status', $event->status === 'archived' ? 'archived' : (! $live ? 'setup' : ($dirty ? 'live_unpublished_changes' : 'live')));
            $event->unsetRelation('publications');
        });
        return Inertia::render('Dashboard', ['events' => $events, 'isAdmin' => $user->isAdmin(), 'isSupport' => $user->isSupport()]);
    }
}
