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
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_editor_and_admin_can_self_publish_but_other_customers_cannot(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();
        $editor = User::factory()->create(['role' => 'customer', 'customer_id' => $event->customer_id]);
        $event->members()->attach($editor, ['role' => 'editor']);
        $other = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);

        $this->actingAs($other)->post(route('events.publication.publish', $event))->assertForbidden();
        $this->actingAs($editor)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 1, 'published_by' => $editor->id]);

        $event->update(['venue' => 'Admin changed venue']);
        $this->actingAs($admin)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 2, 'published_by' => $admin->id]);

        $event->update(['venue' => 'Owner changed venue']);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 3, 'published_by' => $owner->id]);
    }

    public function test_first_publish_requires_no_review_metadata_and_creates_version_one(): void
    {
        [$event, $owner] = $this->eventWithUsers();
        $this->assertNull($event->submitted_snapshot_hash);
        $this->assertNull($event->approved_snapshot_hash);

        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $event->refresh();

        $this->assertSame('published', $event->status);
        $this->assertNull($event->submitted_snapshot_hash);
        $this->assertNull($event->approved_snapshot_hash);
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 1]);
    }

    public function test_working_changes_keep_version_one_live_until_customer_publishes_version_two(): void
    {
        [$event, $owner] = $this->eventWithUsers();
        $romantic = $event->template;
        $cinematic = $this->template('modern-cinematic', InvitationTemplateSettings::modernCinematicDefaults());
        $event->templateSetting()->create(['settings' => ['palette_key' => 'burgundy', 'font_pair_key' => 'classic']]);
        $event->activities()->create(['title' => 'Ceremony', 'activity_type' => 'ceremony', 'starts_at' => '2027-10-12 16:00:00+00', 'is_active' => true]);
        $meal = $event->mealOptions()->create(['name' => 'Chicken', 'is_active' => true]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'The Smith Family', 'maximum_party_size' => 2]);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $v1 = $event->latestPublication();
        $newParty = InvitationParty::create(['event_id' => $event->id, 'name' => 'Added after publish', 'maximum_party_size' => 1]);
        $this->actingAs($owner)->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('workflow.invitation_status', 'live')
            ->where('workflow.can_publish', false)
        );
        $this->get(route('public.invitation.show', $newParty->rsvp_token))->assertInertia(fn (Assert $page) => $page->where('party.name', 'Added after publish'));

        $event->update(['venue' => 'Grand Ballroom', 'template_id' => $cinematic->id, 'rsvp_deadline' => now()->subDay()->toDateString()]);
        $event->templateSetting->update(['settings' => ['palette_key' => 'midnight_gold', 'font_pair_key' => 'cinematic_serif']]);
        $event->activities()->first()->update(['title' => 'Later Ceremony']);
        $meal->update(['name' => 'Working Fish', 'is_active' => false]);

        $this->actingAs($owner)->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('workflow.invitation_status', 'live_unpublished_changes')
            ->where('workflow.unpublished_changes', true)
            ->where('workflow.can_publish', true)
        );
        $this->actingAs($owner)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', 'modern-cinematic')
            ->where('invitation.event.venue', 'Grand Ballroom')
        );
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', $romantic->component_key)
            ->where('invitation.event.venue', 'Cedar Hall')
            ->where('invitation.event.activities.0.title', 'Ceremony')
            ->where('meals.0.name', 'Chicken')
            ->where('closed', false)
        );
        $this->assertSame('Cedar Hall', $v1->fresh()->snapshot['event']['venue']);

        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 2]);
        $this->assertSame([1, 2], $event->publications()->orderBy('version')->pluck('version')->all());
        $this->assertSame('Cedar Hall', $v1->fresh()->snapshot['event']['venue']);
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', 'modern-cinematic')
            ->where('invitation.event.venue', 'Grand Ballroom')
            ->where('invitation.event.activities.0.title', 'Later Ceremony')
            ->has('meals', 0)
            ->where('closed', true)
        );
    }

    public function test_publishing_without_changes_does_not_create_a_duplicate_version(): void
    {
        [$event, $owner] = $this->eventWithUsers();
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertSessionHasErrors('workflow');

        $this->assertDatabaseCount('event_publications', 1);
    }

    public function test_publish_readiness_and_archiving_remain_enforced(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();
        $event->update(['main_date' => null, 'start_time' => null, 'template_id' => null]);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertSessionHasErrors(['main_date', 'start_time', 'template_id']);

        $event->update(['main_date' => '2027-10-12', 'start_time' => '16:00', 'template_id' => $this->template('romantic-floral', InvitationTemplateSettings::defaults())->id]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Party', 'maximum_party_size' => 1]);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->actingAs($admin)->patch(route('events.publication.archive', $event))->assertRedirect();
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertNotFound();
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertStatus(422);
    }

    public function test_customer_wizard_is_authorized_persists_its_steps_and_uses_trusted_design_settings(): void
    {
        [$event, $owner] = $this->eventWithUsers(['main_date' => null, 'start_time' => null, 'template_id' => null]);
        $other = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);
        $template = $this->template('romantic-floral', InvitationTemplateSettings::defaults());

        $this->actingAs($owner)->get(route('events.setup', $event))->assertInertia(fn (Assert $page) => $page->component('Events/Setup')->where('step', 1));
        $this->actingAs($other)->get(route('events.setup', $event))->assertForbidden();

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'details']), ['event_type' => 'wedding', 'title' => 'Wizard Wedding', 'host_name' => 'Maya', 'second_host_name' => 'Elias', 'description' => 'Welcome.'])->assertRedirect(route('events.setup', ['event' => $event, 'step' => 2]));
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Wizard Wedding', 'host_name' => 'Maya']);

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), ['main_date' => '2027-10-12', 'start_time' => '16:00', 'end_time' => '18:00', 'event_timezone' => 'America/New_York', 'venue' => 'Cedar Hall', 'address' => '1 Cedar Street'])->assertRedirect(route('events.setup', ['event' => $event, 'step' => 3]));
        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_timezone' => 'America/New_York', 'venue' => 'Cedar Hall']);
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), ['event_timezone' => 'EST'])->assertSessionHasErrors('event_timezone');

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'rsvp']), ['rsvp_deadline' => '2027-10-10'])->assertRedirect(route('events.setup', ['event' => $event, 'step' => 4]));
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['template_id' => $template->id, 'settings' => ['palette_key' => 'not-trusted']])->assertSessionHasErrors('settings.palette_key');
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['template_id' => $template->id, 'settings' => []])->assertRedirect(route('events.setup', ['event' => $event, 'step' => 2]));
        $this->assertDatabaseHas('events', ['id' => $event->id, 'template_id' => $template->id]);
    }

    public function test_wizard_steps_are_freely_available_but_completion_comes_only_from_saved_event_data(): void
    {
        [$event, $owner] = $this->eventWithUsers(['title' => null, 'event_type' => null, 'host_name' => null, 'main_date' => null, 'start_time' => null, 'rsvp_deadline' => null, 'template_id' => null]);

        foreach ([1, 2] as $step) {
            $this->actingAs($owner)->get(route('events.setup', ['event' => $event, 'step' => $step]))
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Events/Setup')
                    ->where('step', $step)
                    ->where('steps.details', false)
                    ->where('steps.location', false)
                    ->where('steps.schedule', false)
                    ->where('steps.design', false));
        }

        $event->update(['event_type' => 'wedding', 'title' => 'Saved Event', 'host_name' => 'Maya', 'main_date' => '2027-10-12', 'start_time' => '16:00', 'event_timezone' => 'America/New_York', 'venue' => null, 'address' => null]);

        $this->actingAs($owner)->get(route('events.setup', ['event' => $event, 'step' => 5]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('steps.details', true)
                ->where('steps.location', true)
                ->where('steps.schedule', false));
        $this->assertDatabaseCount('event_publications', 0);
    }

    public function test_customer_sees_only_active_customer_selectable_templates_and_cannot_craft_a_legacy_selection(): void
    {
        [$event, $owner] = $this->eventWithUsers(['template_id' => null]);
        $romantic = $this->template('romantic-floral', InvitationTemplateSettings::defaults());
        $editorial = $this->template('editorial-luxury', InvitationTemplateSettings::editorialLuxuryDefaults());
        $cinematic = $this->template('modern-cinematic', InvitationTemplateSettings::modernCinematicDefaults());
        Template::query()->where('component_key', 'romantic-floral')->where('id', '!=', $romantic->id)->firstOrFail()->update(['is_customer_selectable' => false]);
        $romantic->update(['display_order' => 1]);
        $editorial->update(['display_order' => 2]);
        $cinematic->update(['display_order' => 3]);
        $legacy = Template::create(['name' => 'Modern Minimal', 'slug' => 'modern-minimal-'.uniqid(), 'component_key' => 'modern-minimal', 'default_settings' => [], 'is_active' => true, 'is_customer_selectable' => false]);
        Template::create(['name' => 'Elegant Classic', 'slug' => 'elegant-classic-'.uniqid(), 'component_key' => 'elegant-classic', 'default_settings' => [], 'is_active' => true, 'is_customer_selectable' => false]);
        $inactive = Template::create(['name' => 'Inactive Romantic', 'slug' => 'inactive-romantic-'.uniqid(), 'component_key' => 'romantic-floral', 'default_settings' => [], 'is_active' => false, 'is_customer_selectable' => true]);

        $this->actingAs($owner)->get(route('events.setup', ['event' => $event, 'step' => 4]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('templates.0.id', $romantic->id)
                ->where('templates.1.id', $editorial->id)
                ->where('templates.2.id', $cinematic->id)
                ->has('templates', 3));

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['template_id' => $legacy->id, 'settings' => []])
            ->assertSessionHasErrors('template_id');
        $this->assertDatabaseMissing('events', ['id' => $event->id, 'template_id' => $legacy->id]);
        $this->assertNotNull($inactive);
    }

    public function test_customer_can_keep_an_existing_inactive_legacy_template_but_cannot_select_it_for_another_event(): void
    {
        [$event, $owner] = $this->eventWithUsers();
        $legacy = Template::create(['name' => 'Legacy', 'slug' => 'legacy-'.uniqid(), 'component_key' => 'modern-minimal', 'default_settings' => [], 'is_active' => false, 'is_customer_selectable' => false]);
        $event->update(['template_id' => $legacy->id]);
        Template::query()->where('id', '!=', $legacy->id)->update(['is_customer_selectable' => false]);

        $this->actingAs($owner)->get(route('events.setup', ['event' => $event, 'step' => 4]))
            ->assertInertia(fn (Assert $page) => $page->where('templates.0.id', $legacy->id));
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['template_id' => $legacy->id, 'settings' => []])
            ->assertRedirect();
    }

    public function test_customer_with_one_unpublished_event_is_sent_to_setup_but_multiple_events_are_not_ambiguous(): void
    {
        [$event, $owner] = $this->eventWithUsers();
        $owner->update(['password' => Hash::make('secret-password')]);

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password'])->assertRedirect(route('events.setup', $event));

        auth()->logout();
        $second = $this->eventForCustomer($owner->customer);
        $second->members()->attach($owner, ['role' => 'editor']);
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'secret-password'])->assertRedirect(route('dashboard'));
    }

    private function eventWithUsers(array $overrides = []): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventForCustomer($customer, $overrides);
        $event->members()->attach($owner, ['role' => 'owner']);

        return [$event, $owner, $admin];
    }

    private function eventForCustomer(Customer $customer, array $overrides = []): Event
    {
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $template = $this->template('romantic-floral', InvitationTemplateSettings::defaults());

        return Event::create([...[
            'customer_id' => $customer->id, 'event_package_id' => $package->id, 'template_id' => $template->id,
            'title' => 'Event '.uniqid(), 'event_type' => 'wedding', 'host_name' => 'Maya', 'event_timezone' => 'America/New_York',
            'main_date' => '2027-10-12', 'start_time' => '16:00', 'venue' => 'Cedar Hall', 'rsvp_deadline' => now()->addWeek()->toDateString(), 'status' => 'draft',
        ], ...$overrides]);
    }

    private function template(string $componentKey, array $settings): Template
    {
        return Template::create(['name' => str($componentKey)->headline(), 'slug' => $componentKey.'-'.uniqid(), 'component_key' => $componentKey, 'default_settings' => $settings, 'is_active' => true, 'is_customer_selectable' => in_array($componentKey, ['romantic-floral', 'editorial-luxury', 'modern-cinematic'], true)]);
    }
}
