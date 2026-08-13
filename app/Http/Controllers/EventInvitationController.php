<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Support\InvitationPresenter;
use App\Support\InvitationTemplateSettings;
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
            ->orderBy('display_order')->orderBy('name')->get()
            ->filter(fn (Template $template) => $template->id === $event->template_id || ! $template->supported_event_types || in_array($event->event_type, $template->supported_event_types, true))
            ->map(fn (Template $template) => $this->templateForSelection($template))->values();

        return Inertia::render('Events/Invitation', [
            'event' => ['id' => $event->id, 'title' => $event->title, 'template_id' => $event->template_id, 'template' => $event->template ? $this->templateForSelection($event->template) : null],
            'templates' => $templates,
            'overrides' => $event->templateSetting?->settings ?? [],
            'resolvedSettings' => InvitationTemplateSettings::resolve($event->template?->default_settings, $event->templateSetting?->settings),
            'sectionKeys' => InvitationTemplateSettings::SECTION_KEYS,
            'headingFonts' => InvitationTemplateSettings::HEADING_FONTS,
            'bodyFonts' => InvitationTemplateSettings::BODY_FONTS,
            'alignments' => InvitationTemplateSettings::ALIGNMENTS,
            'layoutVariants' => InvitationTemplateSettings::LAYOUT_VARIANTS,
            'parties' => $event->invitationParties()->orderBy('name')->get(['id', 'name']),
            'canManage' => $request->user()->can('update', $event),
            'previewInvitation' => $presenter->present($event),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->manage($request, $event);
        $data = $request->validate(['template_id' => ['nullable', 'integer', 'exists:templates,id'], 'settings' => ['nullable', 'array']]);
        $template = isset($data['template_id']) ? Template::findOrFail($data['template_id']) : null;

        if ($template && ! $template->is_active && $template->id !== $event->template_id) {
            throw ValidationException::withMessages(['template_id' => 'Inactive templates cannot be selected for an Event.']);
        }
        if ($template && $template->supported_event_types && ! in_array($event->event_type, $template->supported_event_types, true)) {
            throw ValidationException::withMessages(['template_id' => 'This template does not support the selected Event type.']);
        }

        $event->update(['template_id' => $template?->id]);
        $settings = InvitationTemplateSettings::validate($data['settings'] ?? []);
        if ($request->has('settings')) {
            $event->templateSetting()->updateOrCreate([], ['settings' => $settings]);
        }

        return back()->with('success', 'Invitation template settings saved.');
    }

    public function preview(Request $request, Event $event, InvitationPresenter $presenter): Response
    {
        $this->view($request, $event);
        $party = $request->filled('party_id')
            ? $event->invitationParties()->findOrFail($request->integer('party_id'))
            : null;

        return Inertia::render('Events/InvitationPreview', ['invitation' => $presenter->present($event, $party)]);
    }

    private function view(Request $request, Event $event): void { abort_unless($request->user()->can('view', $event), 403); }
    private function manage(Request $request, Event $event): void { abort_unless($request->user()->can('update', $event), 403); }
    private function templateForSelection(Template $template): array { return ['id' => $template->id, 'name' => $template->name, 'description' => $template->description, 'component_key' => $template->component_key, 'is_active' => $template->is_active, 'default_settings' => $template->default_settings]; }
}
