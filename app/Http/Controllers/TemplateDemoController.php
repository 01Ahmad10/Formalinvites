<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateDemoController extends Controller
{
    public function __invoke(?string $template = null): Response
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless($template === null || in_array($template, ['1', '2', '3', '4', '5', '6', '7'], true), 404);

        $media = [];
        foreach (config('template_demo_assets')[$template] ?? [] as $role => $path) {
            if ($this->assetPath($path) !== null) {
                $media[$role] = route('template-demos.assets', [$template, $role], false);
            }
        }

        return Inertia::render('TemplateDemo', ['number' => $template, 'media' => (object) $media]);
    }

    public function asset(string $template, string $role): BinaryFileResponse
    {
        abort_unless(app()->environment('local'), 404);
        $relative = config('template_demo_assets')[$template][$role] ?? null;
        $path = is_string($relative) ? $this->assetPath($relative) : null;
        abort_if($path === null, 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    private function assetPath(string $relative): ?string
    {
        $root = realpath(storage_path('app/qa/reference-template-assets'));
        $path = $root === false ? false : realpath($root.DIRECTORY_SEPARATOR.$relative);

        return $path !== false && is_file($path) && str_starts_with($path, $root.DIRECTORY_SEPARATOR)
            ? $path : null;
    }
}
