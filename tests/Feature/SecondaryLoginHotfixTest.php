<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondaryLoginHotfixTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_adds_secondary_login_to_same_client_and_it_receives_current_and_future_invitation_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Client']);
        $primary = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'customer_account_role' => 'primary']);
        $package = EventPackage::create(['name' => 'Package', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 50, 'is_active' => true]);
        $existing = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'guest_capacity' => 20]);
        $existing->members()->attach($primary, ['role' => 'editor']);
        $futureEntitlement = InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 30]);

        $this->actingAs($admin)->post(route('admin.customers.second-login.store', $customer), [
            'name' => 'Secondary QA', 'email' => 'secondary-qa@example.test', 'password' => 'safe-password',
        ])->assertRedirect()->assertSessionHas('success', 'Second client login added.');

        $secondary = User::where('email', 'secondary-qa@example.test')->sole();
        $this->assertSame('customer', $secondary->role);
        $this->assertSame('secondary', $secondary->customer_account_role);
        $this->assertSame($customer->id, $secondary->customer_id);
        $this->assertDatabaseHas('event_user', ['event_id' => $existing->id, 'user_id' => $secondary->id]);
        $this->assertTrue($primary->fresh()->is($customer->users()->where('customer_account_role', 'primary')->sole()));

        $this->actingAs($secondary)->get(route('events.show', $existing))->assertOk();
        $this->actingAs($secondary)->post(route('events.store'), ['start_setup' => true, 'entitlement_id' => $futureEntitlement->id])->assertRedirect();
        $future = Event::where('customer_id', $customer->id)->where('id', '!=', $existing->id)->sole();
        $this->assertDatabaseHas('event_user', ['event_id' => $future->id, 'user_id' => $secondary->id]);

        $other = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);
        $this->actingAs($other)->get(route('events.show', $existing))->assertForbidden();
        $this->actingAs($other)->get(route('events.show', $future))->assertForbidden();
    }

    public function test_third_login_and_invalid_or_duplicate_secondary_details_are_validation_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Client']);
        User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'customer_account_role' => 'primary']);
        User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'customer_account_role' => 'secondary']);

        $this->actingAs($admin)->post(route('admin.customers.second-login.store', $customer), ['name' => 'Third', 'email' => 'third@example.test', 'password' => 'safe-password'])
            ->assertSessionHasErrors('customer_id');
        $this->assertDatabaseMissing('users', ['email' => 'third@example.test']);

        $available = Customer::create(['name' => 'Available']);
        User::factory()->create(['role' => 'customer', 'customer_id' => $available->id, 'customer_account_role' => 'primary', 'email' => 'existing@example.test']);
        $this->actingAs($admin)->post(route('admin.customers.second-login.store', $available), ['name' => '', 'email' => 'existing@example.test', 'password' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertSame(1, $available->users()->where('role', 'customer')->count());
    }
}
