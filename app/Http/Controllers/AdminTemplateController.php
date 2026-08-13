<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Template;
use App\Support\InvitationTemplateSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Templates', [
            'templates' => Template::query()->withCount('events')->orderBy('display_order')->orderBy('name')->get(),
            'componentKeys' => Template::COMPONENT_KEYS,
            'eventTypes' => Event::TYPES,
            'defaultSettings' => InvitationTemplateSettings::defaults(),
            'headingFonts' => InvitationTemplateSettings::HEADING_FONTS,
            'bodyFonts' => InvitationTemplateSettings::BODY_FONTS,
            'alignments' => InvitationTemplateSettings::ALIGNMENTS,
            'layoutVariants' => InvitationTemplateSettings::LAYOUT_VARIANTS,
            'sectionKeys' => InvitationTemplateSettings::SECTION_KEYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Template::create($this->validated($request));

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $template->update($this->validated($request, $template));

        return back()->with('success', 'Template updated.');
    }

    public function setActive(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $template->update($data);

        return back()->with('success', $data['is_active'] ? 'Template activated.' : 'Template deactivated. Existing Event assignments were preserved.');
    }

    private function validated(Request $request, ?Template $template = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('templates', 'slug')->ignore($template)],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:100'],
            'component_key' => ['required', Rule::in(Template::COMPONENT_KEYS)],
            'supported_event_types' => ['nullable', 'array'],
            'supported_event_types.*' => [Rule::in(Event::TYPES)],
            'default_settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['default_settings'] = InvitationTemplateSettings::validate($data['default_settings'] ?? []);

        return $data;
    }
}
