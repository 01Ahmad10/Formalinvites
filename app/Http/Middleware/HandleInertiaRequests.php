<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'customerEvents' => fn () => $request->user()?->role === 'customer'
                    ? $request->user()->managedEvents()->select(['events.id', 'events.title', 'events.status'])->withExists('publications')->orderBy('events.main_date')->orderBy('events.id')->get()
                        ->map(fn ($event) => ['id' => $event->id, 'title' => $event->title ?: 'Your invitation', 'is_live' => $event->status !== 'archived' && $event->publications_exists, 'is_archived' => $event->status === 'archived'])
                        ->values()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
