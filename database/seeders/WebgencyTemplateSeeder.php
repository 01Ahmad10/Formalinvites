<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Support\InvitationTemplateSettings;
use Illuminate\Database\Seeder;

class WebgencyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['dolce-vita', 'Dolce Vita', 'An Italian garden invitation with a date reveal and letter opening.', '#fffdfb', '#7a9aaa'],
            ['blossom-oud', 'Blossom & Oud', 'An ivory and gold invitation with an ornate Moroccan frame.', '#f9e6d4', '#866739'],
            ['sacred-garden', 'The Sacred Garden', 'A garden invitation with floral paper details and a wax seal.', '#f9f0e0', '#a67d2b'],
        ] as $index => [$key, $name, $description, $paper, $accent]) {
            Template::firstOrCreate(['slug' => $key], [
                'name' => $name, 'component_key' => $key, 'description' => $description,
                'category' => 'wedding', 'supported_event_types' => ['wedding', 'engagement'],
                'is_active' => true, 'is_customer_selectable' => true, 'display_order' => 6 + $index,
                'default_settings' => [...InvitationTemplateSettings::defaults(),
                    'palette_key' => 'reference', 'font_pair_key' => 'classic',
                    'palettes' => ['reference' => ['label' => 'Original design', 'primary_color' => $accent, 'secondary_color' => $accent, 'accent_color' => $accent, 'background_color' => $paper, 'text_color' => '#4a4a4a']],
                    'font_pairs' => ['classic' => ['label' => 'Original typography', 'heading_font' => 'classic_serif', 'body_font' => 'serif']],
                ],
            ]);
        }
    }
}
