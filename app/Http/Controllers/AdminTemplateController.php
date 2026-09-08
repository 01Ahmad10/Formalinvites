<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Templates', [
            'templates' => Template::query()->withCount('events')->orderBy('display_order')->orderBy('name')->get()->each->append('demo_url'),
        ]);
    }

    public function setActive(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $template->update($data);

        return back()->with('success', $data['is_active'] ? 'Template activated.' : 'Template deactivated. Existing Event assignments were preserved.');
    }
}
