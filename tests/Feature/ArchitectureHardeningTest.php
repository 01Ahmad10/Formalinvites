<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Event;
use App\Models\InvitationParty;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArchitectureHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_supported_roles_can_authenticate(): void
    {
        $legacyUser = User::factory()->create(['role' => 'support']);

        $this->post('/login', ['email' => $legacyUser->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_removed_registration_and_generic_admin_user_routes_are_unavailable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get('/register')->assertNotFound();
        $this->actingAs($admin)->get('/admin/users')->assertNotFound();
    }

    public function test_high_volume_admin_and_guest_lists_are_paginated_at_twenty_five_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customers = collect(range(1, 26))->map(fn (int $number) => Customer::create(['name' => "Customer {$number}"]));
        foreach ($customers as $customer) {
            Payment::create(['customer_id' => $customer->id, 'original_amount' => 10, 'discount' => 0, 'final_amount' => 10, 'paid_amount' => 0, 'status' => 'unpaid']);
        }

        $this->actingAs($admin)->get(route('admin.customers.index'))->assertInertia(fn (Assert $page) => $page
            ->has('customers', 25)->where('pagination.total', 26)
        );
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertInertia(fn (Assert $page) => $page
            ->has('payments', 25)->where('pagination.total', 26)
        );

        $event = Event::create(['customer_id' => $customers->first()->id, 'title' => 'Guest pagination', 'event_type' => 'wedding', 'host_name' => 'Host']);
        foreach (range(1, 26) as $number) {
            InvitationParty::create(['event_id' => $event->id, 'name' => "Party {$number}", 'maximum_party_size' => 1]);
        }

        $this->actingAs($admin)->get(route('events.guests.index', $event))->assertInertia(fn (Assert $page) => $page
            ->has('parties', 25)->where('pagination.total', 26)
        );
    }
}
