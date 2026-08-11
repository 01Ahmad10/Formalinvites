<?php

namespace App\Policies;

use App\Models\EventPackage;
use App\Models\User;

class EventPackagePolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'support'], true); }
    public function view(User $user, EventPackage $package): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, EventPackage $package): bool { return $user->isAdmin(); }
}
