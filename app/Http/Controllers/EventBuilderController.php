<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventGiftMethod;
use App\Models\Template;
use App\Support\EventPublicationService;
use App\Support\EventTiming;
use App\Support\InvitationPresenter;
use App\Support\InvitationTemplateSettings;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventBuilderController extends Controller
{
    public function show(Request $request, Event $event, InvitationPresenter $presenter): Response|RedirectResponse
    {
        $this->view($request, $event);

        if ($event->isArchived()) return to_route('events.show', $event);
        if (! $this->isReadyForPersonalization($event)) return to_route('events.setup', $event);

        $event->load(['template', 'templateSetting', 'invitationContent']);

        return Inertia::render('Events/Builder', [
            'event' => $this->eventForBuilder($event),
            'templates' => $this->templates($event, $request),
            'activities' => $event->activities()->orderBy('display_order')->orderBy('starts_at')->get()->map(fn ($activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'activity_type' => $activity->activity_type,
                'starts_at' => $activity->starts_at->setTimezone($event->event_timezone)->format('F j, Y g:i A'),
                'is_active' => $activity->is_active,
            ])->values(),
            'meals' => $event->mealOptions()->orderBy('display_order')->get(['id', 'name', 'is_active']),
            'giftMethods' => $event->giftMethods()->orderBy('display_order')->orderBy('id')->get(['id', 'label', 'details', 'external_url', 'display_order', 'is_active']),
            'previewParties' => $event->invitationParties()->where('is_active', true)->with(['members', 'rsvp.personResponses.mealOption'])->orderBy('name')->get()->map(fn ($party) => \App\Support\InvitationPreviewContext::party($party))->values(),
            'previewInvitation' => $presenter->present($event),
            'timezones' => DateTimeZone::listIdentifiers(),
            'urls' => [
                'save' => route('events.builder.update', $event),
                'publish' => route('events.publication.publish', $event),
                'dashboard' => route('dashboard'),
                'invitation' => route('events.show', $event),
                'preview' => route('events.invitation.preview', $event),
                'activities' => route('events.activities.index', $event),
                'meals' => route('events.meals.index', $event),
                'guests' => route('events.guests.index', $event),
            ],
            'isLive' => $event->isLive(),
        ]);
    }

    public function update(Request $request, Event $event, EventPublicationService $publications): RedirectResponse
    {
        $this->manage($request, $event);
        $data = $this->validated($request, $event);

        DB::transaction(function () use ($event, $data, $request, $publications): void {
            $locked = Event::query()->lockForUpdate()->findOrFail($event->id);
            abort_if($locked->isArchived(), 422, 'Archived Events cannot be changed.');
            $template = Template::findOrFail($data['template_id']);
            $this->assertTemplateIsSelectable($request, $locked, $template, $data['event_type']);
            $settings = InvitationTemplateSettings::validateEventSettings($data['settings'] ?? [], $template->default_settings);
            $content = $data['content'] ?? [];
            $giftMethods = $data['gift_methods'] ?? [];
            unset($data['settings'], $data['content'], $data['gift_methods']);
            $locked->update($data);
            $locked->templateSetting()->updateOrCreate([], ['settings' => $settings]);
            $locked->invitationContent()->updateOrCreate([], $content);
            $this->syncGiftMethods($locked, $giftMethods);
            $publications->publishIfActiveLocked($locked, $request->user());
        });

        return back()->with('success', 'Invitation changes saved.');
    }

    private function validated(Request $request, Event $event): array
    {
        $data = $request->validate([
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'host_name' => ['required', 'string', 'max:255'],
            'second_host_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
            'rsvp_deadline' => ['nullable', 'date', 'before_or_equal:main_date'],
            'template_id' => ['required', 'integer', 'exists:templates,id'],
            'settings' => ['nullable', 'array'],
            'content' => ['required', 'array'],
            'content.primary_locale' => ['required', Rule::in(['en', 'ar'])],
            'content.story_enabled' => ['required', 'boolean'],
            'content.story_heading' => ['nullable', 'string', 'max:255'],
            'content.story_body' => ['nullable', 'string', 'max:10000'],
            'content.gift_registry_enabled' => ['required', 'boolean'],
            'content.gift_registry_intro' => ['nullable', 'string', 'max:5000'],
            'content.ending_enabled' => ['required', 'boolean'],
            'content.ending_title' => ['nullable', 'string', 'max:255'],
            'content.ending_message' => ['nullable', 'string', 'max:5000'],
            'gift_methods' => ['nullable', 'array', 'max:12'],
            'gift_methods.*.id' => ['nullable', 'integer'],
            'gift_methods.*.label' => ['required', 'string', 'max:255'],
            'gift_methods.*.details' => ['nullable', 'string', 'max:5000'],
            'gift_methods.*.external_url' => ['nullable', 'url', 'max:2048'],
            'gift_methods.*.display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'gift_methods.*.is_active' => ['required', 'boolean'],
        ]);

        return EventTiming::deriveEndDate($data, $data['event_timezone']);
    }

    private function templates(Event $event, Request $request): array
    {
        return Template::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $event->template_id))
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where(fn ($query) => $query->where('is_customer_selectable', true)->orWhere('id', $event->template_id)))
            ->orderBy('display_order')->orderBy('name')->get()
            ->filter(fn (Template $template) => $template->id === $event->template_id || ! $template->supported_event_types || in_array($event->event_type, $template->supported_event_types, true))
            ->map(fn (Template $template) => ['id' => $template->id, 'name' => $template->name, 'description' => $template->description, 'component_key' => $template->component_key, 'demo_url' => $template->demo_url, 'preview_media' => (object) \App\Support\LocalInvitationMedia::forTemplate($template->component_key), ...InvitationTemplateSettings::selectionOptions($template->default_settings)])
            ->values()->all();
    }

    private function eventForBuilder(Event $event): array
    {
        return [
            'id' => $event->id, 'event_type' => $event->event_type, 'title' => $event->title, 'host_name' => $event->host_name,
            'second_host_name' => $event->second_host_name, 'description' => $event->description,
            'main_date' => $event->getRawOriginal('main_date') ? substr($event->getRawOriginal('main_date'), 0, 10) : null,
            'start_time' => $event->start_time ? substr($event->start_time, 0, 5) : null,
            'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : null,
            'event_timezone' => $event->event_timezone, 'venue' => $event->venue, 'address' => $event->address,
            'location_url' => $event->location_url, 'dress_code' => $event->dress_code,
            'parking_information' => $event->parking_information, 'transportation_information' => $event->transportation_information,
            'accommodation_information' => $event->accommodation_information, 'guest_information' => $event->guest_information,
            'rsvp_deadline' => $event->getRawOriginal('rsvp_deadline') ? substr($event->getRawOriginal('rsvp_deadline'), 0, 10) : null,
            'template_id' => $event->template_id, 'settings' => $event->templateSetting?->settings ?? [],
            'content' => [
                'primary_locale' => $event->invitationContent?->primary_locale ?? 'en',
                'story_enabled' => (bool) $event->invitationContent?->story_enabled,
                'story_heading' => $event->invitationContent?->story_heading,
                'story_body' => $event->invitationContent?->story_body,
                'gift_registry_enabled' => (bool) $event->invitationContent?->gift_registry_enabled,
                'gift_registry_intro' => $event->invitationContent?->gift_registry_intro,
                'ending_enabled' => (bool) $event->invitationContent?->ending_enabled,
                'ending_title' => $event->invitationContent?->ending_title,
                'ending_message' => $event->invitationContent?->ending_message,
            ],
        ];
    }

    private function syncGiftMethods(Event $event, array $methods): void
    {
        $ids = collect($methods)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        if ($ids->isNotEmpty() && $event->giftMethods()->whereIn('id', $ids)->count() !== $ids->count()) {
            throw ValidationException::withMessages(['gift_methods' => 'A gift method does not belong to this Event.']);
        }

        $event->giftMethods()->whereNotIn('id', $ids)->delete();
        foreach ($methods as $index => $method) {
            $attributes = [
                'label' => $method['label'], 'details' => $method['details'] ?? null,
                'external_url' => $method['external_url'] ?? null,
                'display_order' => $method['display_order'] ?? $index,
                'is_active' => $method['is_active'],
            ];
            if (! empty($method['id'])) EventGiftMethod::query()->where('event_id', $event->id)->whereKey($method['id'])->update($attributes);
            else $event->giftMethods()->create($attributes);
        }
    }

    private function assertTemplateIsSelectable(Request $request, Event $event, Template $template, string $eventType): void
    {
        if (! $request->user()->isAdmin() && ! $template->is_customer_selectable && $template->id !== $event->template_id) throw ValidationException::withMessages(['template_id' => 'This invitation design is not available for customer selection.']);
        if (! $template->is_active && $template->id !== $event->template_id) throw ValidationException::withMessages(['template_id' => 'Inactive templates cannot be selected for an Event.']);
        if ($template->supported_event_types && ! in_array($eventType, $template->supported_event_types, true)) throw ValidationException::withMessages(['template_id' => 'This template does not support the selected Event type.']);
    }

    private function isReadyForPersonalization(Event $event): bool
    {
        return filled($event->title)
            && filled($event->event_type)
            && filled($event->host_name)
            && $event->main_date !== null
            && filled($event->start_time)
            && filled($event->event_timezone)
            && $event->template_id !== null;
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
}
