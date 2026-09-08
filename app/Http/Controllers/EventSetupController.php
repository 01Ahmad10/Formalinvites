<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Template;
use App\Support\InvitationPresenter;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\InvitationTemplateSettings;
use App\Support\EventPublicationService;
use App\Support\EventTiming;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
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
        $step = min(max($request->integer('step', 1), 1), 2);

        return Inertia::render('Events/Setup', [
            'event' => $event,
            'step' => $step,
            'templates' => $this->templates($event, $request->user()),
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
            'steps' => [
                'details' => filled($event->event_type) && filled($event->title) && filled($event->host_name),
                'location' => $event->main_date !== null && filled($event->start_time) && in_array($event->event_timezone, DateTimeZone::listIdentifiers(), true),
                'schedule' => $event->rsvp_deadline !== null || $event->activities()->exists() || $event->mealOptions()->exists(),
                'design' => $event->template !== null,
            ],
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function save(Request $request, Event $event, string $step, EventPublicationService $publications): RedirectResponse
    {
        $this->manage($request, $event);
        $data = match ($step) {
            'details' => $this->details($request),
            'location' => $this->location($request),
            'rsvp' => $this->rsvp($request, $event),
            'information' => $this->information($request),
            'design' => null,
            default => abort(404),
        };

        DB::transaction(function () use ($event, $step, $data, $request, $publications): void {
            $locked = Event::query()->lockForUpdate()->findOrFail($event->id);
            abort_if($locked->status === 'archived', 422, 'Archived Events cannot be changed.');
            if ($step === 'design') $this->design($request, $locked);
            else $locked->update($data);
            $publications->publishIfActiveLocked($locked, $request->user());
        });

        if ($step === 'information') return to_route('events.builder', $event)->with('success', 'Your invitation is ready to personalize.');

        $next = ['details' => 2, 'location' => 3, 'rsvp' => 4, 'design' => 2][$step];

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

        return EventTiming::deriveEndDate($data, $data['event_timezone']);
    }

    private function rsvp(Request $request, Event $event): array
    {
        $data = $request->validate(['rsvp_deadline' => ['nullable', 'date']]);
        if (! empty($data['rsvp_deadline']) && $event->main_date && $data['rsvp_deadline'] > $event->main_date->format('Y-m-d')) {
            throw ValidationException::withMessages(['rsvp_deadline' => 'RSVP deadline must be on or before the Event date.']);
        }

        return $data;
    }

    private function information(Request $request): array
    {
        $request->validate(['main_date' => ['required', 'date'], 'start_time' => ['required', 'date_format:H:i']]);
        $details = $this->details($request);
        $location = $this->location($request);
        $rsvp = $request->validate(['rsvp_deadline' => ['nullable', 'date']]);

        if (! empty($rsvp['rsvp_deadline']) && ! empty($location['main_date']) && $rsvp['rsvp_deadline'] > $location['main_date']) {
            throw ValidationException::withMessages(['rsvp_deadline' => 'RSVP deadline must be on or before the Event date.']);
        }

        return [...$details, ...$location, ...$rsvp];
    }

    private function design(Request $request, Event $event): void
    {
        $data = $request->validate(['template_id' => ['required', 'integer', 'exists:templates,id'], 'settings' => ['nullable', 'array']]);
        $template = Template::findOrFail($data['template_id']);

        if (! $request->user()->isAdmin() && ! $template->is_customer_selectable && $template->id !== $event->template_id) {
            throw ValidationException::withMessages(['template_id' => 'This invitation design is not available for customer selection.']);
        }

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

    private function templates(Event $event, $user): array
    {
        return Template::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $event->template_id))
            ->when(! $user->isAdmin(), fn ($query) => $query->where(fn ($query) => $query->where('is_customer_selectable', true)->orWhere('id', $event->template_id)))
            ->orderBy('display_order')->orderBy('name')->get()
            ->filter(fn (Template $template) => $template->id === $event->template_id || ! $template->supported_event_types || in_array($event->event_type, $template->supported_event_types, true))
            ->map(fn (Template $template) => ['id' => $template->id, 'name' => $template->name, 'description' => $template->description, 'component_key' => $template->component_key, 'demo_url' => $template->demo_url, 'is_active' => $template->is_active, ...InvitationTemplateSettings::selectionOptions($template->default_settings)])
            ->values()->all();
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
}
