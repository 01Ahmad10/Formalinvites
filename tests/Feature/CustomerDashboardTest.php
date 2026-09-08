<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_receives_an_invitation_focused_dashboard_with_exact_capacity_and_response_metrics(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'name' => 'Maya Customer']);
        $package = EventPackage::create(['name' => '250 Guests', 'minimum_guests' => 201, 'maximum_guests' => 250, 'price' => 100, 'is_active' => true]);
        $event = $this->event($customer, $package, 230, 'My Wedding');
        $event->members()->attach($user, ['role' => 'owner']);
        $event->update(['status' => 'published']);
        EventPublication::create(['event_id' => $event->id, 'version' => 1, 'snapshot' => [], 'snapshot_hash' => str_repeat('a', 64), 'published_at' => now()]);

        $attending = InvitationParty::create(['event_id' => $event->id, 'name' => 'Attending Family', 'maximum_party_size' => 4]);
        $declined = InvitationParty::create(['event_id' => $event->id, 'name' => 'Declined Family', 'maximum_party_size' => 3]);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Awaiting Family', 'maximum_party_size' => 2]);
        $inactive = InvitationParty::create(['event_id' => $event->id, 'name' => 'Inactive Family', 'maximum_party_size' => 5, 'is_active' => false]);
        $attending->rsvp->update(['status' => 'attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $declined->rsvp->update(['status' => 'not_attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $attending->rsvp->personResponses()->createMany([
            ['first_name' => 'Maya', 'member_type' => 'adult', 'is_attending' => true],
            ['first_name' => 'Elias', 'member_type' => 'adult', 'is_attending' => true],
        ]);
        $inactive->rsvp->update(['status' => 'attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $inactive->rsvp->personResponses()->create(['first_name' => 'Inactive', 'member_type' => 'adult', 'is_attending' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('CustomerDashboard')
            ->missing('analytics')
            ->has('invitations', 1)
            ->where('invitations.0.id', $event->id)
            ->where('invitations.0.status', 'live')
            ->where('invitations.0.guest_capacity', 230)
            ->where('invitations.0.allocated_capacity', 9)
            ->where('invitations.0.families_invited', 3)
            ->where('invitations.0.responded_families', 2)
            ->where('invitations.0.confirmed_attendees', 2)
            ->where('invitations.0.response_rate', 67)
            ->where('invitations.0.next_action.title', 'Keep your invitation up to date')
            ->where('auth.customerEvents.0.id', $event->id)
        );
    }

    public function test_customer_dashboard_only_contains_events_the_customer_is_authorized_to_manage(): void
    {
        $first = Customer::create(['name' => 'First']);
        $second = Customer::create(['name' => 'Second']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $first->id]);
        $owned = $this->event($first, null, null, 'Owned Invitation');
        $foreign = $this->event($second, null, null, 'Private Invitation');
        $owned->members()->attach($user, ['role' => 'owner']);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('CustomerDashboard')
            ->has('invitations', 1)
            ->where('invitations.0.id', $owned->id)
            ->where('auth.customerEvents.0.id', $owned->id)
            ->missing('analytics')
        )->assertDontSee($foreign->title);
    }

    public function test_archived_customer_invitation_is_review_only_on_the_dashboard(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = $this->event($customer, null, null, 'Archived Invitation');
        $event->update(['status' => 'archived']);
        $event->members()->attach($user, ['role' => 'owner']);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('CustomerDashboard')
            ->where('invitations.0.status', 'archived')
            ->where('invitations.0.next_action.label', 'View Invitation')
            ->where('auth.customerEvents.0.is_archived', true)
            ->missing('analytics')
        );
    }

    public function test_multiple_customer_events_are_presented_as_an_invitation_selector(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $first = $this->event($customer, null, null, 'First Invitation');
        $second = $this->event($customer, null, null, 'Second Invitation');
        $first->members()->attach($user, ['role' => 'owner']);
        $second->members()->attach($user, ['role' => 'owner']);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('CustomerDashboard')
            ->has('invitations', 2)
            ->has('auth.customerEvents', 2)
            ->missing('analytics')
        );
        $this->actingAs($user)->get(route('events.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('isAdmin', false)
            ->has('events', 2)
            ->where('customers', [])
        );
    }

    public function test_admin_dashboard_remains_the_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('analytics.kpis')
            ->missing('invitations')
            ->where('auth.customerEvents', [])
        );
    }

    private function event(Customer $customer, ?EventPackage $package, ?int $capacity, string $title): Event
    {
        return Event::create([
            'customer_id' => $customer->id,
            'event_package_id' => $package?->id,
            'guest_capacity' => $capacity,
            'title' => $title,
            'event_type' => 'wedding',
            'host_name' => 'Host',
            'main_date' => '2027-06-10',
            'status' => 'draft',
        ]);
    }
}
