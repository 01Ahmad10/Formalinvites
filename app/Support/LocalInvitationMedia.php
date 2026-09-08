<?php

namespace App\Support;

class LocalInvitationMedia
{
    public static function forTemplate(?string $key): array
    {
        if (! app()->environment('local')) return [];

        $number = ['romantic-floral' => 1, 'editorial-luxury' => 2, 'modern-cinematic' => 3, 'dolce-vita' => 5, 'blossom-oud' => 6, 'sacred-garden' => 7][$key ?? ''] ?? null;
        $root = realpath(storage_path('app/qa/reference-template-assets'));
        if (! $number || ! $root) return [];

        $media = [];
        foreach (config('template_demo_assets')[$number] ?? [] as $role => $relative) {
            $path = realpath($root.DIRECTORY_SEPARATOR.$relative);
            if ($path && is_file($path) && str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
                $media[$role] = route('template-demos.assets', [$number, $role], false);
            }
        }

        // Presentation only: never persisted in template settings or publication snapshots.
        return $media;
    }
}
