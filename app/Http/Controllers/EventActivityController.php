<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventActivity;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventActivityController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $this->view($request, $event);

        return Inertia::render('Events/Activities/Index', [
            'event' => $this->eventForPresentation($event),
            'activities' => $event->activities()->orderBy('display_order')->orderBy('starts_at')->get()->map(fn ($activity) => $this->activityForPresentation($activity, $event))->values(),
            'canManage' => $request->user()->can('update', $event),
        ]);
    }

    public function create(Request $request, Event $event): Response
    {
        $this->manage($request, $event);

        return Inertia::render('Events/Activities/Form', ['event' => $this->eventForPresentation($event), 'activity' => null]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->manage($request, $event);
        $event->activities()->create($this->validated($request, $event));

        return to_route('events.activities.index', $event)->with('success', 'Activity added.');
    }

    public function show(Request $request, Event $event, EventActivity $activity): Response
    {
        $this->view($request, $event);
        $activity = $this->activityForEvent($event, $activity);

        return Inertia::render('Events/Activities/Show', ['event' => $this->eventForPresentation($event), 'activity' => $this->activityForPresentation($activity, $event), 'canManage' => $request->user()->can('update', $event)]);
    }

    public function edit(Request $request, Event $event, EventActivity $activity): Response
    {
        $this->manage($request, $event);
        $activity = $this->activityForEvent($event, $activity);

        return Inertia::render('Events/Activities/Form', ['event' => $this->eventForPresentation($event), 'activity' => $this->activityForForm($activity, $event)]);
    }

    public function update(Request $request, Event $event, EventActivity $activity): RedirectResponse
    {
        $this->manage($request, $event);
        $activity = $this->activityForEvent($event, $activity);
        $activity->update($this->validated($request, $event));

        return to_route('events.activities.show', [$event, $activity])->with('success', 'Activity updated.');
    }

    public function setActive(Request $request, Event $event, EventActivity $activity): RedirectResponse
    {
        $this->manage($request, $event);
        $activity = $this->activityForEvent($event, $activity);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $activity->update($data);

        return back()->with('success', $data['is_active'] ? 'Activity activated.' : 'Activity deactivated.');
    }

    private function validated(Request $request, Event $event): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['nullable', 'date', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:end_date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_url' => ['nullable', 'url', 'max:2048'],
            'location_notes' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $timezone = $event->event_timezone;
        $startsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data['start_date']} {$data['start_time']}", $timezone);
        $endsAt = isset($data['end_date'], $data['end_time']) ? CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data['end_date']} {$data['end_time']}", $timezone) : null;

        if ($endsAt && ! $endsAt->greaterThan($startsAt)) {
            throw ValidationException::withMessages(['end_time' => 'End date and time must be after the start date and time.']);
        }

        return [
            'title' => $data['title'], 'activity_type' => $data['activity_type'] ?? null, 'description' => $data['description'] ?? null,
            'starts_at' => $startsAt->utc(), 'ends_at' => $endsAt?->utc(), 'venue' => $data['venue'] ?? null,
            'address' => $data['address'] ?? null, 'location_url' => $data['location_url'] ?? null, 'location_notes' => $data['location_notes'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
        ];
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function activityForEvent(Event $event, EventActivity $activity): EventActivity { abort_unless($activity->event_id === $event->id, 404); return $activity; }
    private function eventForPresentation(Event $event): array { return ['id' => $event->id, 'title' => $event->title, 'event_timezone' => $event->event_timezone]; }
    private function activityForPresentation(EventActivity $activity, Event $event): array { $start = $activity->starts_at->setTimezone($event->event_timezone); $end = $activity->ends_at?->setTimezone($event->event_timezone); return ['id' => $activity->id, 'title' => $activity->title, 'activity_type' => $activity->activity_type, 'description' => $activity->description, 'date' => $start->format('F j, Y'), 'start_time' => $start->format('g:i A'), 'end_date' => $end?->format('F j, Y'), 'end_time' => $end?->format('g:i A'), 'venue' => $activity->venue, 'address' => $activity->address, 'location_url' => $activity->location_url, 'location_notes' => $activity->location_notes, 'display_order' => $activity->display_order, 'is_active' => $activity->is_active, 'created_at' => $activity->created_at?->setTimezone($event->event_timezone)->format('F j, Y \\a\\t g:i A'), 'updated_at' => $activity->updated_at?->setTimezone($event->event_timezone)->format('F j, Y \\a\\t g:i A')]; }
    private function activityForForm(EventActivity $activity, Event $event): array { $start = $activity->starts_at->setTimezone($event->event_timezone); $end = $activity->ends_at?->setTimezone($event->event_timezone); return ['id' => $activity->id, 'title' => $activity->title, 'activity_type' => $activity->activity_type, 'description' => $activity->description, 'start_date' => $start->format('Y-m-d'), 'start_time' => $start->format('H:i'), 'end_date' => $end?->format('Y-m-d'), 'end_time' => $end?->format('H:i'), 'venue' => $activity->venue, 'address' => $activity->address, 'location_url' => $activity->location_url, 'location_notes' => $activity->location_notes, 'display_order' => $activity->display_order]; }
}
