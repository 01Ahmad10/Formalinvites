<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventPublication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Creates immutable versions while the caller holds the Event lock. */
class EventPublicationService
{
    public function __construct(private readonly InvitationPublicationSnapshotBuilder $snapshots) {}

    public function activateLocked(Event $event, User $user): EventPublication
    {
        $this->ensureReady($event);
        $snapshot = $this->snapshots->build($event);
        $hash = $this->snapshots->hashSnapshot($snapshot);
        $latest = $event->publications()->orderByDesc('version')->lockForUpdate()->first();

        if ($latest && hash_equals($latest->snapshot_hash, $hash)) return $latest;

        $publication = $event->publications()->create([
            'version' => ($latest?->version ?? 0) + 1,
            'snapshot' => $snapshot,
            'snapshot_hash' => $hash,
            'published_by' => $user->id,
            'published_at' => now(),
        ]);
        $event->update(['status' => 'published']);

        return $publication;
    }

    public function publishIfActiveLocked(Event $event, User $user): ?EventPublication
    {
        return $event->publications()->exists() ? $this->activateLocked($event, $user) : null;
    }

    private function ensureReady(Event $event): void
    {
        $event->loadMissing('template');
        $errors = [];
        foreach (['title' => 'Event title', 'event_type' => 'Event type', 'host_name' => 'Host name', 'main_date' => 'Event date', 'start_time' => 'Start time'] as $field => $label) {
            if (blank($event->{$field})) $errors[$field] = "{$label} is required before activating the invitation.";
        }
        if (! in_array($event->event_timezone, \DateTimeZone::listIdentifiers(), true)) $errors['event_timezone'] = 'A valid Event timezone is required before activating the invitation.';
        if (! $event->template_id || ! $event->template) $errors['template_id'] = 'Choose an invitation Template before activating the invitation.';
        if ($errors) throw ValidationException::withMessages($errors);
    }
}
