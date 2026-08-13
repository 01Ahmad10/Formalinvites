<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class InvitationTemplateSettings
{
    public const HEADING_FONTS = ['elegant_serif', 'classic_serif', 'modern_sans'];
    public const BODY_FONTS = ['serif', 'sans'];
    public const ALIGNMENTS = ['left', 'center'];
    public const LAYOUT_VARIANTS = ['standard', 'centered'];
    public const SECTION_KEYS = ['hero', 'hosts', 'main_date', 'schedule', 'location', 'guest_information', 'dress_code', 'accommodation', 'rsvp'];

    public static function defaults(): array
    {
        return [
            'primary_color' => '#C9A96E',
            'secondary_color' => '#243B53',
            'heading_font' => 'elegant_serif',
            'body_font' => 'sans',
            'text_alignment' => 'center',
            'layout_variant' => 'standard',
            'sections' => array_fill_keys(self::SECTION_KEYS, true),
        ];
    }

    public static function resolve(?array $defaults, ?array $overrides): array
    {
        $base = self::merge(self::defaults(), $defaults ?? []);

        return self::merge($base, $overrides ?? []);
    }

    public static function validate(array $settings): array
    {
        $allowed = ['primary_color', 'secondary_color', 'heading_font', 'body_font', 'text_alignment', 'layout_variant', 'sections'];
        $unknown = array_diff(array_keys($settings), $allowed);
        if ($unknown) self::fail('settings', 'Unknown template setting: '.implode(', ', $unknown).'.');

        $validated = [];
        foreach (['primary_color', 'secondary_color'] as $key) {
            if (array_key_exists($key, $settings)) {
                if (! is_string($settings[$key]) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $settings[$key])) self::fail("settings.{$key}", 'Use a six-digit hexadecimal color, such as #C9A96E.');
                $validated[$key] = strtoupper($settings[$key]);
            }
        }
        foreach ([['heading_font', self::HEADING_FONTS], ['body_font', self::BODY_FONTS], ['text_alignment', self::ALIGNMENTS], ['layout_variant', self::LAYOUT_VARIANTS]] as [$key, $values]) {
            if (array_key_exists($key, $settings)) {
                if (! in_array($settings[$key], $values, true)) self::fail("settings.{$key}", 'This setting is not supported.');
                $validated[$key] = $settings[$key];
            }
        }
        if (array_key_exists('sections', $settings)) {
            if (! is_array($settings['sections'])) self::fail('settings.sections', 'Section settings must be an object.');
            $unknownSections = array_diff(array_keys($settings['sections']), self::SECTION_KEYS);
            if ($unknownSections) self::fail('settings.sections', 'Unknown presentation section: '.implode(', ', $unknownSections).'.');
            foreach ($settings['sections'] as $key => $visible) {
                if (! is_bool($visible)) self::fail("settings.sections.{$key}", 'Section visibility must be true or false.');
            }
            $validated['sections'] = $settings['sections'];
        }

        return $validated;
    }

    private static function merge(array $base, array $values): array
    {
        foreach ($values as $key => $value) {
            $base[$key] = $key === 'sections' && is_array($value) ? array_replace($base['sections'] ?? [], $value) : $value;
        }

        return $base;
    }

    private static function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
