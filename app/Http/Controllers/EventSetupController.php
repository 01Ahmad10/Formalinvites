<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Template;
use App\Support\InvitationPresenter;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\InvitationTemplateSettings;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventSetupController extends Controller
{
    public function show(Request $request, Event $event, InvitationPresenter $presenter, InvitationPublicationSnapshotBuilder $snapshots): Response
    {
        $this->view($request, $event);
        $event->load(['template', 'templateSetting']);
        $currentHash = $snapshots->hash($event);
        $live = $event->publications()->orderByDesc('version')->first();
        $step = min(max($request->integer('step', 1), 1), 5);

        return Inertia::render('Events/Setup', [
            'event' => $event,
            'step' => $step,
            'templates' => $this->templates($event),
            'overrides' => $event->templateSetting?->settings ?? [],
            'resolvedSettings' => InvitationTemplateSettings::resolve($event->template?->default_settings, $event->templateSetting?->settings),
            'activities' => $event->activities()->orderBy('display_order')->orderBy('starts_at')->get()->map(fn ($activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'is_active' => $activity->is_active,
                'starts_at' => $activity->starts_at->setTimezone($event->event_timezone)->format('F j, Y g:i A'),
            ])->values(),
            'meals' => $event->mealOptions()->orderBy('display_order')->get(['id', 'name', 'description', 'is_active', 'display_order']),
            'previewInvitation' => $presenter->present($event),
            'workflow' => [
                'live_version' => $live?->version,
                'unpublished_changes' => $live && ! hash_equals($live->snapshot_hash, $currentHash),
                'archived' => $event->status === 'archived',
                'can_manage' => $request->user()->can('update', $event),
            ],
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function save(Request $request, Event $event, string $step): RedirectResponse
    {
        $this->manage($request, $event);

        match ($step) {
            'details' => $event->update($this->details($request)),
            'location' => $event->update($this->location($request)),
            'rsvp' => $event->update($this->rsvp($request, $event)),
            'design' => $this->design($request, $event),
            default => abort(404),
        };

        $next = ['details' => 2, 'location' => 3, 'rsvp' => 4, 'design' => 5][$step];

        return to_route('events.setup', ['event' => $event, 'step' => $next])->with('success', 'Invitation setup saved.');
    }

    private function details(Request $request): array
    {
        return $request->validate([
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'host_name' => ['required', 'string', 'max:255'],
            'second_host_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function location(Request $request): array
    {
        $data = $request->validate([
            'main_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'event_timezone' => ['required', 'timezone:all'],
            'venue' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_url' => ['nullable', 'url', 'max:2048'],
            'dress_code' => ['nullable', 'string'],
            'parking_information' => ['nullable', 'string'],
            'transportation_information' => ['nullable', 'string'],
            'accommodation_information' => ['nullable', 'string'],
            'guest_information' => ['nullable', 'string'],
        ]);

        if (! empty($data['start_time']) && ! empty($data['end_time']) && $data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'End time must be after start time.']);
        }

        return $data;
    }

    private function rsvp(Request $request, Event $event): array
    {
        $data = $request->validate(['rsvp_deadline' => ['nullable', 'date']]);
        if (! empty($data['rsvp_deadline']) && $event->main_date && $data['rsvp_deadline'] > $event->main_date->format('Y-m-d')) {
            throw ValidationException::withMessages(['rsvp_deadline' => 'RSVP deadline must be on or before the Event date.']);
        }

        return $data;
    }

    private function design(Request $request, Event $event): void
    {
        $data = $request->validate(['template_id' => ['required', 'integer', 'exists:templates,id'], 'settings' => ['nullable', 'array']]);
        $template = Template::findOrFail($data['template_id']);

        if (! $template->is_active && $template->id !== $event->template_id) {
            throw ValidationException::withMessages(['template_id' => 'Inactive templates cannot be selected for an Event.']);
        }
        if ($template->supported_event_types && ! in_array($event->event_type, $template->supported_event_types, true)) {
            throw ValidationException::withMessages(['template_id' => 'This template does not support the selected Event type.']);
        }

        $settings = InvitationTemplateSettings::validateEventSettings($data['settings'] ?? [], $template->default_settings);
        $event->update(['template_id' => $template->id]);
        $event->templateSetting()->updateOrCreate([], ['settings' => $settings]);
    }

    private function templates(Event $event): array
    {
        return Template::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $event->template_id))
            ->orderBy('display_order')->orderBy('name')->get()
            ->filter(fn (Template $template) => $template->id === $event->template_id || ! $template->supported_event_types || in_array($event->event_type, $template->supported_event_types, true))
            ->map(fn (Template $template) => ['id' => $template->id, 'name' => $template->name, 'description' => $template->description, 'component_key' => $template->component_key, 'is_active' => $template->is_active, ...InvitationTemplateSettings::selectionOptions($template->default_settings)])
            ->values()->all();
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
}
