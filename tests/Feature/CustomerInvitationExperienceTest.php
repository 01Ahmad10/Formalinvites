<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationEntitlement;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerInvitationExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_exposes_the_authoritative_zero_invitation_allowance(): void
    {
        $customer = Customer::create(['name' => 'First Client', 'allowed_events' => 2]);
        $package = EventPackage::create(['name' => 'Two slots', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 50, 'is_active' => true]);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 10]);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 10]);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('CustomerDashboard')
            ->where('invitations', [])
            ->where('allowance.allowed_events', 2)
            ->where('allowance.used_events', 0)
            ->where('allowance.remaining_events', 2)
            ->where('allowance.can_create_event', true)
            ->missing('analytics'));
    }

    public function test_customer_can_start_an_allowance_gated_invitation_then_complete_it_live_without_approval(): void
    {
        $this->seed(DatabaseSeeder::class);
        $customer = Customer::create(['name' => 'Journey Client', 'allowed_events' => 1]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $package = EventPackage::query()->firstOrFail();
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => $package->minimum_guests]);

        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true]);

        $event = Event::query()->where('customer_id', $customer->id)->sole();
        $this->assertTrue($event->members()->whereKey($owner)->exists());
        $this->assertSame('draft', $event->status);
        $this->assertNull($event->template_id);

        $this->actingAs($owner)->get(route('events.setup', $event))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Setup')
            ->where('step', 1)
            ->has('templates', 6)
            ->where('templates.0.name', 'Romantic Floral')
            ->where('templates.5.name', 'The Sacred Garden'));

        $template = Template::where('slug', 'romantic-floral')->sole();
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['event_type' => 'wedding', 'template_id' => $template->id, 'settings' => []])
            ->assertRedirect(route('events.setup', ['event' => $event, 'step' => 2]));
        $this->assertDatabaseCount('event_publications', 0);

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'information']), [
            'title' => 'Ava and Leo', 'host_name' => 'Ava', 'second_host_name' => 'Leo',
            'main_date' => '2027-10-12', 'start_time' => '16:00', 'end_time' => '18:00',
            'event_timezone' => 'America/New_York', 'venue' => 'Cedar Hall', 'address' => '1 Cedar Street',
            'location_url' => 'https://example.test/cedar-hall', 'rsvp_deadline' => '2027-10-10', 'description' => 'Join us.',
        ])->assertRedirect(route('events.builder', $event));

        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'The Example Family', 'maximum_party_size' => 2]);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect(route('dashboard'));

        $this->assertSame('published', $event->fresh()->status);
        $this->assertDatabaseCount('event_publications', 1);
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertOk();
    }

    public function test_customer_cannot_start_an_invitation_when_the_allowance_is_exhausted(): void
    {
        $customer = Customer::create(['name' => 'Exhausted Client', 'allowed_events' => 1]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $existing = Event::create(['customer_id' => $customer->id, 'event_timezone' => 'America/New_York', 'status' => 'draft']);
        $existing->members()->attach($owner, ['role' => 'owner']);
        $package = EventPackage::create(['name' => 'Exhausted package', 'minimum_guests' => 1, 'maximum_guests' => 20, 'price' => 20, 'is_active' => true]);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 10, 'status' => InvitationEntitlement::CLAIMED, 'claimed_event_id' => $existing->id, 'claimed_at' => now()]);

        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true])->assertSessionHasErrors('entitlement_id');
        $this->assertSame(1, $customer->fresh()->usedEvents());
    }
}
