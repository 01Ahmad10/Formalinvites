<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Models\User;
use App\Support\InvitationTemplateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvitationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_catalog_templates_round_trip_through_customer_selection_and_admin_views(): void
    {
        $this->seed(\Database\Seeders\WebgencyTemplateSeeder::class);
        [$event, $editor] = $this->eventWithEditor();
        $event->update(['main_date' => now()->addMonth()->toDateString()]);
        $admin = User::factory()->create(['role' => 'admin']);
        $outsider = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other customer'])->id]);

        foreach (Template::orderBy('display_order')->get() as $template) {
            $this->actingAs($editor)->get(route('events.setup', $event))->assertInertia(fn (Assert $page) => $page
                ->has('templates', 3));
            $this->actingAs($outsider)->patch(route('events.setup.save', ['event' => $event, 'step' => 'design']), ['template_id' => $template->id])->assertForbidden();
            $this->actingAs($editor)->patch(route('events.setup.save', ['event' => $event, 'step' => 'design']), ['template_id' => $template->id, 'settings' => []])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($template->id, $event->fresh()->template_id);
            $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('invitations.0.template_name', $template->name));
            $this->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
                ->where('invitation.template.component_key', $template->component_key)->where('invitation.event.host_name', 'Host')
                ->missing('invitation.media')->missing('invitation.reference_demo'));
            $this->actingAs($admin)->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page->where('event.template.name', $template->name));
            $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('analytics.upcoming.0.template_name', $template->name));
        }
        $this->actingAs($admin)->get(route('admin.templates.index'))->assertInertia(fn (Assert $page) => $page->has('templates', 3));
        $first = Template::first();
        $first->update(['is_active' => false]);
        $this->seed(\Database\Seeders\WebgencyTemplateSeeder::class);
        $this->assertFalse($first->fresh()->is_active);
        $this->assertSame(3, Template::count());
        $this->assertNotContains('royal-plum', Template::COMPONENT_KEYS);
    }

    public function test_admin_can_only_toggle_catalog_templates_and_customers_cannot_access_the_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Customer'])->id]);
        $this->actingAs($customer)->get(route('admin.templates.index'))->assertForbidden();
        $template = $this->template();
        $this->actingAs($admin)->patch(route('admin.templates.active', $template), ['is_active' => false])->assertRedirect();
        $this->assertDatabaseHas('templates', ['id' => $template->id, 'is_active' => false]);
    }

    public function test_authorized_event_editor_can_choose_an_active_templates_approved_palette_and_font_pair(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $event->update(['main_date' => '2026-08-12']);
        $template = $this->template();

        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['palette_key' => 'ivory', 'font_pair_key' => 'classic'],
        ])->assertRedirect();

        $settings = $event->fresh()->templateSetting->settings;
        $this->assertSame(['palette_key' => 'ivory', 'font_pair_key' => 'classic'], $settings);
        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.settings.palette_key', 'ivory')
            ->where('invitation.settings.primary_color', '#8A6F3D')
            ->where('invitation.settings.font_pair_key', 'classic')
            ->where('invitation.settings.body_font', 'serif')
            ->where('invitation.event.main_date', 'Wednesday, August 12, 2026')
        );
    }

    public function test_template_defaults_are_used_when_no_palette_or_font_pair_is_selected(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $template = $this->template();

        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $template->id, 'settings' => []])->assertRedirect();

        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.settings.palette_key', 'classic')
            ->where('invitation.settings.primary_color', '#C9A96E')
            ->where('invitation.settings.font_pair_key', 'elegant')
            ->where('invitation.settings.heading_font', 'elegant_serif')
            ->missing('invitation.event.customer_id')
            ->missing('invitation.event.package')
            ->missing('invitation.event.payment')
        );
    }

    public function test_editorial_luxury_is_selectable_with_only_its_approved_palettes_and_typography(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $template = $this->template([
            'name' => 'Editorial Luxury',
            'slug' => 'editorial-luxury',
            'category' => 'editorial',
            'component_key' => 'editorial-luxury',
            'default_settings' => InvitationTemplateSettings::editorialLuxuryDefaults(),
        ]);

        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['palette_key' => 'deep_emerald', 'font_pair_key' => 'contemporary'],
        ])->assertRedirect();

        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', 'editorial-luxury')
            ->where('invitation.settings.palette_key', 'deep_emerald')
            ->where('invitation.settings.primary_color', '#123D35')
            ->where('invitation.settings.font_pair_key', 'contemporary')
            ->where('invitation.settings.heading_font', 'modern_sans')
        );

        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['palette_key' => 'burgundy'],
        ])->assertSessionHasErrors('settings.palette_key');
        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['font_pair_key' => 'classic'],
        ])->assertSessionHasErrors('settings.font_pair_key');
    }

    public function test_modern_cinematic_is_active_selectable_and_accepts_only_its_approved_settings(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $template = $this->template([
            'name' => 'Modern Cinematic',
            'slug' => 'modern-cinematic',
            'category' => 'cinematic',
            'component_key' => 'modern-cinematic',
            'default_settings' => InvitationTemplateSettings::modernCinematicDefaults(),
        ]);

        $this->assertTrue($template->is_active);
        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['palette_key' => 'emerald_night', 'font_pair_key' => 'contemporary'],
        ])->assertRedirect();

        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->component('Events/InvitationPreview')
            ->where('invitation.template.component_key', 'modern-cinematic')
            ->where('invitation.settings.palette_key', 'emerald_night')
            ->where('invitation.settings.primary_color', '#D2B06A')
            ->where('invitation.settings.font_pair_key', 'contemporary')
            ->where('invitation.settings.heading_font', 'modern_sans')
        );

        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['palette_key' => 'burgundy'],
        ])->assertSessionHasErrors('settings.palette_key');
        $this->actingAs($editor)->put(route('events.invitation.update', $event), [
            'template_id' => $template->id,
            'settings' => ['font_pair_key' => 'editorial'],
        ])->assertSessionHasErrors('settings.font_pair_key');
    }

    public function test_editorial_luxury_is_seeded_active_without_overriding_a_later_admin_deactivation(): void
    {
        $this->seed();

        $template = Template::where('slug', 'editorial-luxury')->firstOrFail();
        $this->assertTrue($template->is_active);

        $template->update(['is_active' => false]);
        $this->seed();

        $this->assertFalse($template->fresh()->is_active);
    }

    public function test_modern_cinematic_is_seeded_active_without_overriding_a_later_admin_deactivation(): void
    {
        $this->seed();

        $template = Template::where('slug', 'modern-cinematic')->firstOrFail();
        $this->assertTrue($template->is_active);

        $template->update(['is_active' => false]);
        $this->seed();

        $this->assertFalse($template->fresh()->is_active);
    }

    public function test_legacy_event_level_design_settings_are_retained_but_not_used_for_rendering(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $template = $this->template();
        $event->update(['template_id' => $template->id]);
        $event->templateSetting()->create(['settings' => ['primary_color' => '#FF0000', 'heading_font' => 'modern_sans', 'text_alignment' => 'left', 'sections' => ['schedule' => false]]]);

        $this->actingAs($editor)->get(route('events.invitation.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->where('templates.0.palettes.0.key', 'classic')
            ->where('templates.0.font_pairs.0.key', 'elegant')
        );
        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.settings.primary_color', '#C9A96E')
            ->where('invitation.settings.heading_font', 'elegant_serif')
            ->where('invitation.settings.text_alignment', 'center')
            ->where('invitation.settings.sections.schedule', true)
        );
        $this->assertSame('#FF0000', $event->fresh()->templateSetting->settings['primary_color']);
    }

    public function test_invitation_previews_format_event_wall_clock_times_from_the_shared_presenter(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $event->update([
            'main_date' => '2026-08-12',
            'start_time' => '02:22:00',
            'end_time' => '13:00:00',
            'event_timezone' => 'Asia/Beirut',
            'template_id' => $this->template()->id,
        ]);

        $this->actingAs($editor)->get(route('events.invitation.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Invitation')
            ->where('previewInvitation.event.main_date', 'Wednesday, August 12, 2026')
            ->where('previewInvitation.event.start_time', '2:22 AM')
            ->where('previewInvitation.event.end_time', '1:00 PM')
        );

        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->component('Events/InvitationPreview')
            ->where('invitation.event.start_time', '2:22 AM')
            ->where('invitation.event.end_time', '1:00 PM')
        );

        $event->update(['end_time' => null]);

        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.event.start_time', '2:22 AM')
            ->where('invitation.event.end_time', null)
        );
    }

    public function test_inactive_template_cannot_be_newly_selected_but_existing_assignment_is_retained(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $inactive = $this->template(['is_active' => false]);
        $event->update(['template_id' => $inactive->id]);

        $this->actingAs($editor)->get(route('events.invitation.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->where('event.template.id', $inactive->id)
            ->where('templates.0.id', $inactive->id)
        );
        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $inactive->id, 'settings' => ['palette_key' => 'ivory']])->assertRedirect();

        $otherInactive = $this->template(['slug' => 'other-inactive', 'is_active' => false]);
        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $otherInactive->id, 'settings' => []])->assertSessionHasErrors('template_id');
        $this->assertSame($inactive->id, $event->fresh()->template_id);
    }

    public function test_template_switching_preserves_event_guest_and_rsvp_data_and_event_settings_are_independent(): void
    {
        [$first, $editor] = $this->eventWithEditor();
        $second = $this->event($first->customer);
        $second->members()->attach($editor, ['role' => 'editor']);
        $firstTemplate = $this->template(['slug' => 'classic']);
        $secondTemplate = $this->template(['slug' => 'minimal', 'component_key' => 'modern-minimal']);
        $party = InvitationParty::create(['event_id' => $first->id, 'name' => 'Family', 'maximum_party_size' => 2]);
        $party->rsvp->update(['status' => 'attending']);
        $first->update(['dress_code' => 'Formal', 'template_id' => $firstTemplate->id]);

        $this->actingAs($editor)->put(route('events.invitation.update', $first), ['template_id' => $secondTemplate->id, 'settings' => ['palette_key' => 'ivory']])->assertRedirect();
        $this->actingAs($editor)->put(route('events.invitation.update', $second), ['template_id' => $secondTemplate->id, 'settings' => ['palette_key' => 'burgundy']])->assertRedirect();

        $this->assertSame('Formal', $first->fresh()->dress_code);
        $this->assertSame($first->id, $party->fresh()->event_id);
        $this->assertSame('attending', $party->fresh()->rsvp->status);
        $this->actingAs($editor)->get(route('events.invitation.preview', $first))->assertInertia(fn (Assert $page) => $page->where('invitation.settings.primary_color', '#8A6F3D'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('events.invitation.preview', $second))->assertInertia(fn (Assert $page) => $page->where('invitation.settings.primary_color', '#7C2438'));
    }

    public function test_personalized_preview_only_accepts_a_party_owned_by_the_route_event(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $template = $this->template();
        $event->update(['template_id' => $template->id]);
        $sameEventParty = InvitationParty::create(['event_id' => $event->id, 'name' => 'Same Event Party', 'maximum_party_size' => 1]);
        $sameCustomerEvent = $this->event($event->customer);
        $sameCustomerParty = InvitationParty::create(['event_id' => $sameCustomerEvent->id, 'name' => 'Same Customer Party', 'maximum_party_size' => 1]);
        $otherCustomerEvent = $this->event();
        $otherCustomerParty = InvitationParty::create(['event_id' => $otherCustomerEvent->id, 'name' => 'Other Customer Party', 'maximum_party_size' => 1]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($editor)->get(route('events.invitation.preview', ['event' => $event, 'party_id' => $sameEventParty->id]))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.party_name', 'Same Event Party')
        );
        $this->actingAs($editor)->get(route('events.invitation.preview', ['event' => $event, 'party_id' => $sameCustomerParty->id]))->assertNotFound();
        $this->actingAs($admin)->get(route('events.invitation.preview', ['event' => $event, 'party_id' => $otherCustomerParty->id]))->assertNotFound();
        $this->actingAs($editor)->get(route('events.invitation.preview', ['event' => $event, 'party_id' => 999999]))->assertNotFound();
        $this->actingAs($editor)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.party_name', null)
        );
    }

    public function test_event_users_cannot_submit_arbitrary_or_other_template_design_settings(): void
    {
        [$event, $editor] = $this->eventWithEditor();
        $template = $this->template();
        $otherTemplate = $this->template(['slug' => 'other-template', 'default_settings' => [...InvitationTemplateSettings::defaults(), 'palettes' => ['rose' => ['label' => 'Rose', 'primary_color' => '#B76E79', 'secondary_color' => '#5F2834', 'accent_color' => '#C9A96E', 'background_color' => '#FFF9FA', 'text_color' => '#5F2834']]]]);

        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $template->id, 'settings' => ['palette_key' => 'rose']])->assertSessionHasErrors('settings.palette_key');
        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $template->id, 'settings' => ['font_pair_key' => 'untrusted']])->assertSessionHasErrors('settings.font_pair_key');
        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $template->id, 'settings' => ['primary_color' => '#123456']])->assertSessionHasErrors('settings');
        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $template->id, 'settings' => ['heading_font' => 'https://unsafe.example/font']])->assertSessionHasErrors('settings');
        $this->actingAs($editor)->put(route('events.invitation.update', $event), ['template_id' => $template->id, 'settings' => ['text_alignment' => 'left', 'layout_variant' => 'centered', 'sections' => ['schedule' => false]]])->assertSessionHasErrors('settings');

        $this->assertNull($event->fresh()->template_id);
        $this->assertDatabaseMissing('event_template_settings', ['event_id' => $event->id]);
        $this->assertNotNull($otherTemplate);
    }

    public function test_another_customer_cannot_access_event_invitation(): void
    {
        [$event] = $this->eventWithEditor();
        $outsider = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);

        $this->actingAs($outsider)->get(route('events.invitation.edit', $event))->assertForbidden();
        $this->actingAs($outsider)->get(route('events.invitation.preview', $event))->assertForbidden();
    }

    private function eventWithEditor(): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $editor = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = $this->event($customer);
        $event->members()->attach($editor, ['role' => 'editor']);

        return [$event, $editor];
    }

    private function event(?Customer $customer = null): Event
    {
        $customer ??= Customer::create(['name' => 'Customer '.uniqid()]);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);

        return Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Event '.uniqid(), 'event_type' => 'wedding', 'host_name' => 'Host', 'event_timezone' => 'America/New_York', 'status' => 'draft']);
    }

    private function template(array $overrides = []): Template
    {
        return Template::create([...$this->templatePayload(), ...$overrides]);
    }

    private function templatePayload(): array
    {
        return ['name' => 'Elegant Classic', 'slug' => 'elegant-classic', 'description' => 'Safe template', 'category' => 'formal', 'component_key' => 'elegant-classic', 'supported_event_types' => ['wedding'], 'default_settings' => InvitationTemplateSettings::defaults(), 'is_active' => true, 'is_customer_selectable' => true, 'display_order' => 1];
    }
}
