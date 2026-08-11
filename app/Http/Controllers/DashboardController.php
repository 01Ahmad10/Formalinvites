<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = request()->user();
        $relations = ['customer', 'package', 'payments'];
        $events = $user->isAdmin() || $user->isSupport() ? Event::with($relations)->latest()->get() : $user->managedEvents()->with($relations)->latest()->get();
        return Inertia::render('Dashboard', ['events' => $events, 'isAdmin' => $user->isAdmin(), 'isSupport' => $user->isSupport()]);
    }
}
