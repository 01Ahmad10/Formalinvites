<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvitationEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_creates_entitlements_not_a_blank_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = $this->package();

        $this->actingAs($admin)->post(route('admin.clients.store'), [
            'name' => 'New Client', 'entitlements' => [['event_package_id' => $package->id, 'exact_guest_capacity' => 30], ['event_package_id' => $package->id, 'exact_guest_capacity' => 30]],
            'primary_name' => 'Primary', 'primary_email' => 'primary@example.test', 'primary_password' => 'safe-password',
        ])->assertRedirect(route('admin.customers.index'));

        $customer = Customer::where('name', 'New Client')->sole();
        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('invitation_entitlements', 2);
        $this->assertDatabaseHas('invitation_entitlements', ['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 30, 'status' => InvitationEntitlement::AVAILABLE]);
    }

    public function test_an_entitlement_requires_a_package_and_capacity_inside_its_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = $this->package();
        $customer = Customer::create(['name' => 'Client']);
        $entitlement = InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 30]);

        $this->actingAs($admin)->patch(route('admin.customers.invitation-entitlements.update', [$customer, $entitlement]), [
            'event_package_id' => $package->id,
            'exact_guest_capacity' => 51,
        ])->assertSessionHasErrors('exact_guest_capacity');
    }

    public function test_customer_claims_an_available_entitlement_once_and_event_copies_its_package_and_capacity(): void
    {
        [$customer, $owner, $secondary, $package, $entitlement] = $this->customerWithEntitlement();

        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true])->assertRedirect();

        $event = Event::where('customer_id', $customer->id)->sole();
        $this->assertSame($package->id, $event->event_package_id);
        $this->assertSame(30, $event->guest_capacity);
        $this->assertSame(InvitationEntitlement::CLAIMED, $entitlement->fresh()->status);
        $this->assertSame($event->id, $entitlement->fresh()->claimed_event_id);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $owner->id]);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $secondary->id]);

        $this->actingAs($secondary)->get(route('events.show', $event))->assertOk();
        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true])->assertSessionHasErrors('entitlement_id');
        $this->assertDatabaseCount('events', 1);
    }

    public function test_allowance_summary_derives_from_available_and_claimed_entitlements_including_archived_events(): void
    {
        [$customer, $owner, , $package, $first] = $this->customerWithEntitlement();
        $second = InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 30]);
        $archived = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'guest_capacity' => 30, 'status' => 'archived']);
        $second->update(['status' => InvitationEntitlement::CLAIMED, 'claimed_event_id' => $archived->id, 'claimed_at' => now()]);

        $this->actingAs($owner)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('allowance.allowed_events', 2)
            ->where('allowance.used_events', 1)
            ->where('allowance.remaining_events', 1)
            ->where('allowance.can_create_event', true));
        $this->assertTrue($first->fresh()->isAvailable());
    }

    public function test_secondary_login_added_after_an_event_can_access_it_and_other_customers_cannot(): void
    {
        [$customer, $owner, , $package, $entitlement] = $this->customerWithEntitlement(false);
        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true]);
        $event = Event::where('customer_id', $customer->id)->sole();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.customers.second-login.store', $customer), [
            'name' => 'Later Login', 'email' => 'later@example.test', 'password' => 'safe-password',
        ])->assertRedirect();
        $later = User::where('email', 'later@example.test')->sole();
        $other = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);

        $this->actingAs($later)->get(route('events.show', $event))->assertOk();
        $this->actingAs($other)->get(route('events.show', $event))->assertForbidden();
        $this->assertSame(InvitationEntitlement::CLAIMED, $entitlement->fresh()->status);
    }

    public function test_admin_uses_the_same_entitlement_claim_path(): void
    {
        [$customer, , $secondary, $package, $entitlement] = $this->customerWithEntitlement();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.customers.invitations.start', $customer), [
            'entitlement_id' => $entitlement->id,
        ])->assertRedirect();

        $event = Event::where('customer_id', $customer->id)->sole();
        $this->assertSame($package->id, $event->event_package_id);
        $this->assertSame(30, $event->guest_capacity);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $secondary->id]);
        $this->assertSame($event->id, $entitlement->fresh()->claimed_event_id);
    }

    private function customerWithEntitlement(bool $withSecondary = true): array
    {
        $customer = Customer::create(['name' => 'Client '.uniqid(), 'allowed_events' => 1]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'customer_account_role' => 'primary']);
        $secondary = $withSecondary ? User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'customer_account_role' => 'secondary']) : null;
        $package = $this->package();
        $entitlement = InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 30]);

        return [$customer, $owner, $secondary, $package, $entitlement];
    }

    private function package(): EventPackage
    {
        return EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 50, 'is_active' => true]);
    }
}
