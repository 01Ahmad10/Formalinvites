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
            'palette_key' => 'classic',
            'font_pair_key' => 'elegant',
            'palettes' => [
                'classic' => ['label' => 'Classic Gold', 'primary_color' => '#C9A96E', 'secondary_color' => '#243B53', 'accent_color' => '#C9A96E', 'background_color' => '#FFFFFF', 'text_color' => '#243B53'],
                'ivory' => ['label' => 'Ivory', 'primary_color' => '#8A6F3D', 'secondary_color' => '#4A4035', 'accent_color' => '#D8C8A8', 'background_color' => '#FFFCF5', 'text_color' => '#4A4035'],
                'burgundy' => ['label' => 'Burgundy', 'primary_color' => '#7C2438', 'secondary_color' => '#3E1720', 'accent_color' => '#C9A96E', 'background_color' => '#FFF9F8', 'text_color' => '#3E1720'],
            ],
            'font_pairs' => [
                'elegant' => ['label' => 'Elegant', 'heading_font' => 'elegant_serif', 'body_font' => 'sans'],
                'classic' => ['label' => 'Classic', 'heading_font' => 'classic_serif', 'body_font' => 'serif'],
                'modern' => ['label' => 'Modern', 'heading_font' => 'modern_sans', 'body_font' => 'sans'],
            ],
            // These remain Template-owned design choices, not Event overrides.
            'text_alignment' => 'center',
            'layout_variant' => 'standard',
            'sections' => array_fill_keys(self::SECTION_KEYS, true),
        ];
    }

    public static function editorialLuxuryDefaults(): array
    {
        return [
            ...self::defaults(),
            'palette_key' => 'champagne_noir',
            'font_pair_key' => 'editorial',
            'palettes' => [
                'monochrome' => ['label' => 'Monochrome', 'primary_color' => '#111111', 'secondary_color' => '#E9E4DD', 'accent_color' => '#8C8780', 'background_color' => '#F7F4EF', 'text_color' => '#171717'],
                'champagne_noir' => ['label' => 'Champagne Noir', 'primary_color' => '#1F1D1A', 'secondary_color' => '#D5B77A', 'accent_color' => '#A8874A', 'background_color' => '#F7F2E8', 'text_color' => '#1F1D1A'],
                'deep_emerald' => ['label' => 'Deep Emerald', 'primary_color' => '#123D35', 'secondary_color' => '#D7C7A5', 'accent_color' => '#B6965D', 'background_color' => '#F8F4EB', 'text_color' => '#182522'],
                'midnight' => ['label' => 'Midnight', 'primary_color' => '#13223A', 'secondary_color' => '#E9E1D2', 'accent_color' => '#9F8761', 'background_color' => '#F6F3ED', 'text_color' => '#172033'],
            ],
            'font_pairs' => [
                'editorial' => ['label' => 'Editorial', 'heading_font' => 'elegant_serif', 'body_font' => 'sans'],
                'modern_serif' => ['label' => 'Modern Serif', 'heading_font' => 'classic_serif', 'body_font' => 'serif'],
                'contemporary' => ['label' => 'Contemporary', 'heading_font' => 'modern_sans', 'body_font' => 'sans'],
            ],
            'text_alignment' => 'left',
            'layout_variant' => 'standard',
        ];
    }

    public static function modernCinematicDefaults(): array
    {
        return [
            ...self::defaults(),
            'palette_key' => 'midnight_gold',
            'font_pair_key' => 'cinematic_serif',
            'palettes' => [
                'midnight_gold' => ['label' => 'Midnight Gold', 'primary_color' => '#D2B06A', 'secondary_color' => '#111A2F', 'accent_color' => '#B48A43', 'background_color' => '#090B12', 'text_color' => '#F5F1E8'],
                'obsidian' => ['label' => 'Obsidian', 'primary_color' => '#E5E7EB', 'secondary_color' => '#1C2028', 'accent_color' => '#9AA2AE', 'background_color' => '#0B0C0F', 'text_color' => '#F7F7F5'],
                'deep_bordeaux' => ['label' => 'Deep Bordeaux', 'primary_color' => '#D4B183', 'secondary_color' => '#3A101A', 'accent_color' => '#9C5B57', 'background_color' => '#10090B', 'text_color' => '#F7EFE5'],
                'emerald_night' => ['label' => 'Emerald Night', 'primary_color' => '#D2B06A', 'secondary_color' => '#0C342D', 'accent_color' => '#7BAA96', 'background_color' => '#08110F', 'text_color' => '#EFF4EE'],
            ],
            'font_pairs' => [
                'cinematic_serif' => ['label' => 'Cinematic Serif', 'heading_font' => 'classic_serif', 'body_font' => 'sans'],
                'modern_contrast' => ['label' => 'Modern Contrast', 'heading_font' => 'modern_sans', 'body_font' => 'serif'],
                'contemporary' => ['label' => 'Contemporary', 'heading_font' => 'modern_sans', 'body_font' => 'sans'],
            ],
            'text_alignment' => 'left',
            'layout_variant' => 'standard',
        ];
    }

    public static function resolve(?array $templateDefaults, ?array $eventSettings): array
    {
        $definition = self::definition($templateDefaults);
        $eventSettings = is_array($eventSettings) ? $eventSettings : [];
        $paletteKey = self::validKey($eventSettings['palette_key'] ?? null, $definition['palettes']) ? $eventSettings['palette_key'] : $definition['palette_key'];
        $fontPairKey = self::validKey($eventSettings['font_pair_key'] ?? null, $definition['font_pairs']) ? $eventSettings['font_pair_key'] : $definition['font_pair_key'];
        $palette = $definition['palettes'][$paletteKey];
        $fontPair = $definition['font_pairs'][$fontPairKey];

        return [
            'palette_key' => $paletteKey,
            'font_pair_key' => $fontPairKey,
            'primary_color' => $palette['primary_color'],
            'secondary_color' => $palette['secondary_color'],
            'accent_color' => $palette['accent_color'],
            'background_color' => $palette['background_color'],
            'text_color' => $palette['text_color'],
            'heading_font' => $fontPair['heading_font'],
            'body_font' => $fontPair['body_font'],
            'text_alignment' => $definition['text_alignment'],
            'layout_variant' => $definition['layout_variant'],
            'sections' => $definition['sections'],
        ];
    }

    public static function selectionOptions(?array $templateDefaults): array
    {
        $definition = self::definition($templateDefaults);

        return [
            'palettes' => array_map(fn (string $key, array $palette) => ['key' => $key, ...$palette], array_keys($definition['palettes']), $definition['palettes']),
            'font_pairs' => array_map(fn (string $key, array $fontPair) => ['key' => $key, ...$fontPair], array_keys($definition['font_pairs']), $definition['font_pairs']),
            'default_palette_key' => $definition['palette_key'],
            'default_font_pair_key' => $definition['font_pair_key'],
        ];
    }

    public static function validateEventSettings(array $settings, ?array $templateDefaults): array
    {
        $allowed = ['palette_key', 'font_pair_key'];
        $unknown = array_diff(array_keys($settings), $allowed);
        if ($unknown) self::fail('settings', 'Only a Template color style and typography selection may be changed.');

        $definition = self::definition($templateDefaults);
        $validated = [];
        foreach (['palette_key' => 'palettes', 'font_pair_key' => 'font_pairs'] as $key => $definitionKey) {
            if (! array_key_exists($key, $settings)) continue;
            $value = $settings[$key];
            if ($value === null || $value === '') {
                $validated[$key] = null;
                continue;
            }
            if (! is_string($value) || ! self::validKey($value, $definition[$definitionKey])) self::fail("settings.{$key}", 'This selection is not supported by the selected Template.');
            $validated[$key] = $value;
        }

        return array_filter($validated, fn ($value) => $value !== null);
    }

    // Template settings are trusted Admin-owned configuration. Legacy top-level
    // colors/fonts remain supported here as a default Template style only.
    public static function validateTemplateDefinition(array $settings): array
    {
        $allowed = ['palette_key', 'font_pair_key', 'palettes', 'font_pairs', 'primary_color', 'secondary_color', 'heading_font', 'body_font', 'text_alignment', 'layout_variant', 'sections'];
        $unknown = array_diff(array_keys($settings), $allowed);
        if ($unknown) self::fail('default_settings', 'Unknown template setting: '.implode(', ', $unknown).'.');

        $definition = self::definition($settings);

        return [
            'palette_key' => $definition['palette_key'],
            'font_pair_key' => $definition['font_pair_key'],
            'palettes' => $definition['palettes'],
            'font_pairs' => $definition['font_pairs'],
            'text_alignment' => $definition['text_alignment'],
            'layout_variant' => $definition['layout_variant'],
            'sections' => $definition['sections'],
        ];
    }

    private static function definition(?array $settings): array
    {
        $fallback = self::defaults();
        $settings = is_array($settings) ? $settings : [];
        $palettes = self::paletteDefinitions($settings['palettes'] ?? null) ?: $fallback['palettes'];
        $fontPairs = self::fontPairDefinitions($settings['font_pairs'] ?? null) ?: $fallback['font_pairs'];

        $paletteKey = self::validKey($settings['palette_key'] ?? null, $palettes) ? $settings['palette_key'] : array_key_first($palettes);
        $fontPairKey = self::validKey($settings['font_pair_key'] ?? null, $fontPairs) ? $settings['font_pair_key'] : array_key_first($fontPairs);

        // Backward compatibility: an existing trusted Template's legacy values
        // become its default choice. Event-level legacy values are never read.
        if (self::isColor($settings['primary_color'] ?? null) || self::isColor($settings['secondary_color'] ?? null)) {
            $palettes[$paletteKey] = [
                ...$palettes[$paletteKey],
                'primary_color' => self::isColor($settings['primary_color'] ?? null) ? strtoupper($settings['primary_color']) : $palettes[$paletteKey]['primary_color'],
                'secondary_color' => self::isColor($settings['secondary_color'] ?? null) ? strtoupper($settings['secondary_color']) : $palettes[$paletteKey]['secondary_color'],
            ];
        }
        if (in_array($settings['heading_font'] ?? null, self::HEADING_FONTS, true) || in_array($settings['body_font'] ?? null, self::BODY_FONTS, true)) {
            $fontPairs[$fontPairKey] = [
                ...$fontPairs[$fontPairKey],
                'heading_font' => in_array($settings['heading_font'] ?? null, self::HEADING_FONTS, true) ? $settings['heading_font'] : $fontPairs[$fontPairKey]['heading_font'],
                'body_font' => in_array($settings['body_font'] ?? null, self::BODY_FONTS, true) ? $settings['body_font'] : $fontPairs[$fontPairKey]['body_font'],
            ];
        }

        return [
            'palette_key' => $paletteKey,
            'font_pair_key' => $fontPairKey,
            'palettes' => $palettes,
            'font_pairs' => $fontPairs,
            'text_alignment' => in_array($settings['text_alignment'] ?? null, self::ALIGNMENTS, true) ? $settings['text_alignment'] : $fallback['text_alignment'],
            'layout_variant' => in_array($settings['layout_variant'] ?? null, self::LAYOUT_VARIANTS, true) ? $settings['layout_variant'] : $fallback['layout_variant'],
            'sections' => self::sections($settings['sections'] ?? null, $fallback['sections']),
        ];
    }

    private static function paletteDefinitions(mixed $palettes): array
    {
        if (! is_array($palettes)) return [];
        $validated = [];
        foreach ($palettes as $key => $palette) {
            if (! is_string($key) || ! preg_match('/^[a-z0-9_-]+$/', $key) || ! is_array($palette)) continue;
            $colors = ['primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color'];
            if (! collect($colors)->every(fn (string $color) => self::isColor($palette[$color] ?? null))) continue;
            $validated[$key] = ['label' => is_string($palette['label'] ?? null) ? $palette['label'] : str($key)->headline()->toString(), ...collect($colors)->mapWithKeys(fn (string $color) => [$color => strtoupper($palette[$color])])->all()];
        }

        return $validated;
    }

    private static function fontPairDefinitions(mixed $fontPairs): array
    {
        if (! is_array($fontPairs)) return [];
        $validated = [];
        foreach ($fontPairs as $key => $fontPair) {
            if (! is_string($key) || ! preg_match('/^[a-z0-9_-]+$/', $key) || ! is_array($fontPair)) continue;
            if (! in_array($fontPair['heading_font'] ?? null, self::HEADING_FONTS, true) || ! in_array($fontPair['body_font'] ?? null, self::BODY_FONTS, true)) continue;
            $validated[$key] = ['label' => is_string($fontPair['label'] ?? null) ? $fontPair['label'] : str($key)->headline()->toString(), 'heading_font' => $fontPair['heading_font'], 'body_font' => $fontPair['body_font']];
        }

        return $validated;
    }

    private static function sections(mixed $sections, array $fallback): array
    {
        if (! is_array($sections)) return $fallback;

        return array_replace($fallback, array_filter($sections, fn ($visible, $key) => in_array($key, self::SECTION_KEYS, true) && is_bool($visible), ARRAY_FILTER_USE_BOTH));
    }

    private static function validKey(mixed $key, array $values): bool
    {
        return is_string($key) && array_key_exists($key, $values);
    }

    private static function isColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }

    private static function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
