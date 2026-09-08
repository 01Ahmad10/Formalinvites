<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Support\InvitationPresenter;
use App\Support\InvitationTemplateSettings;
use App\Support\EventPublicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventInvitationController extends Controller
{
    public function edit(Request $request, Event $event, InvitationPresenter $presenter): Response
    {
        $this->view($request, $event);
        $event->load(['template', 'templateSetting']);

        $templates = Template::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $event->template_id))
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where(fn ($query) => $query->where('is_customer_selectable', true)->orWhere('id', $event->template_id)))
            ->orderBy('display_order')->orderBy('name')->get()
            ->filter(fn (Template $template) => $template->id === $event->template_id || ! $template->supported_event_types || in_array($event->event_type, $template->supported_event_types, true))
            ->map(fn (Template $template) => $this->templateForSelection($template))->values();

        return Inertia::render('Events/Invitation', [
            'event' => ['id' => $event->id, 'title' => $event->title, 'template_id' => $event->template_id, 'template' => $event->template ? $this->templateForSelection($event->template) : null],
            'templates' => $templates,
            'overrides' => $event->templateSetting?->settings ?? [],
            'resolvedSettings' => InvitationTemplateSettings::resolve($event->template?->default_settings, $event->templateSetting?->settings),
            'parties' => $event->invitationParties()->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()->can('update', $event),
            'previewInvitation' => $presenter->present($event),
            'liveVersion' => $event->publications()->orderByDesc('version')->value('version'),
        ]);
    }

    public function update(Request $request, Event $event, EventPublicationService $publications): RedirectResponse
    {
        $this->manage($request, $event);
        $data = $request->validate(['template_id' => ['nullable', 'integer', 'exists:templates,id'], 'settings' => ['nullable', 'array']]);
        $template = isset($data['template_id']) ? Template::findOrFail($data['template_id']) : null;

        if ($template && ! $request->user()->isAdmin() && ! $template->is_customer_selectable && $template->id !== $event->template_id) {
            throw ValidationException::withMessages(['template_id' => 'This invitation design is not available for customer selection.']);
        }

        if ($template && ! $template->is_active && $template->id !== $event->template_id) {
            throw ValidationException::withMessages(['template_id' => 'Inactive templates cannot be selected for an Event.']);
        }
        if ($template && $template->supported_event_types && ! in_array($event->event_type, $template->supported_event_types, true)) {
            throw ValidationException::withMessages(['template_id' => 'This template does not support the selected Event type.']);
        }

        if (! $template && ! empty($data['settings'])) {
            throw ValidationException::withMessages(['settings' => 'Choose a Template before selecting a color style or typography.']);
        }

        $settings = InvitationTemplateSettings::validateEventSettings($data['settings'] ?? [], $template?->default_settings);
        DB::transaction(function () use ($event, $template, $request, $settings, $publications): void {
            $locked = Event::query()->lockForUpdate()->findOrFail($event->id);
            abort_if($locked->isArchived(), 422, 'Archived Events cannot be changed.');
            $locked->update(['template_id' => $template?->id]);
            if ($request->has('settings')) $locked->templateSetting()->updateOrCreate([], ['settings' => $settings]);
            $publications->publishIfActiveLocked($locked, $request->user());
        });

        return back()->with('success', 'Invitation template settings saved.');
    }

    public function preview(Request $request, Event $event, InvitationPresenter $presenter): Response
    {
        $this->view($request, $event);
        $party = $request->filled('party_id')
            ? $event->invitationParties()->findOrFail($request->integer('party_id'))
            : null;

        $invitation = $presenter->present($event, $party);
        $invitation['preview_context'] = [
            'party' => $party ? \App\Support\InvitationPreviewContext::party($party) : null,
            'meals' => $event->mealOptions()->where('is_active', true)->orderBy('display_order')->get(['id', 'name']),
            'closed' => $event->isRsvpClosed(),
        ];

        return Inertia::render('Events/InvitationPreview', ['invitation' => $invitation]);
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function templateForSelection(Template $template): array { return ['id' => $template->id, 'name' => $template->name, 'description' => $template->description, 'component_key' => $template->component_key, 'demo_url' => $template->demo_url, 'is_active' => $template->is_active, ...InvitationTemplateSettings::selectionOptions($template->default_settings)]; }
}
