<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'customer'], true); }
    public function view(User $user, Event $event): bool { return $user->isAdmin() || ($user->role === 'customer' && $user->customer_id === $event->customer_id && $event->members()->whereKey($user)->exists()); }
    public function create(User $user): bool { return $user->isAdmin() || ($user->role === 'customer' && $user->customer_id !== null); }
    public function update(User $user, Event $event): bool { return $user->isAdmin() || ($user->role === 'customer' && $user->customer_id === $event->customer_id && $event->members()->whereKey($user)->exists()); }
    public function delete(User $user, Event $event): bool { return $user->isAdmin(); }
}
