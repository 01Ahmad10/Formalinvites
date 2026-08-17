<?php

namespace App\Support;

use App\Models\EventPublication;
use App\Models\InvitationParty;

class PublishedInvitationResolver
{
    /** @return array{0: InvitationParty, 1: EventPublication} */
    public function forToken(string $token): array
    {
        $party = InvitationParty::query()->where('rsvp_token', $token)->with(['event', 'members'])->firstOrFail();

        return [$party, $this->forParty($party)];
    }

    public function forParty(InvitationParty $party): EventPublication
    {
        abort_if($party->event->status === 'archived', 404);

        return $party->event->publications()->orderByDesc('version')->firstOrFail();
    }
}
