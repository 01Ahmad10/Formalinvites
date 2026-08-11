<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\InvitationParty;
use App\Models\User;

class InvitationPartyPolicy
{
    public function viewAny(User $user, Event $event): bool { return $user->can('view', $event); }
    public function view(User $user, InvitationParty $party): bool { return $user->can('view', $party->event); }
    public function create(User $user, Event $event): bool { return $user->can('update', $event); }
    public function update(User $user, InvitationParty $party): bool { return $user->can('update', $party->event); }
    public function delete(User $user, InvitationParty $party): bool { return $user->can('update', $party->event); }
}
