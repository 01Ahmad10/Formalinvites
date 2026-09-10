<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminZeroStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_empty_states_distinguish_a_clean_catalogue_from_filtered_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('events.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('events', [])
            ->where('hasEvents', false)
            ->where('isFiltered', false));
        $this->actingAs($admin)->get(route('events.create'))->assertRedirect(route('admin.customers.index'));
        $this->actingAs($admin)->get(route('admin.customers.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Customers')
            ->where('customers', [])
            ->where('hasCustomers', false));
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Payments')
            ->where('hasClients', false));
        $this->actingAs($admin)->get(route('admin.coupons.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Coupons')
            ->where('coupons', []));

        $customer = Customer::create(['name' => 'Visible Client']);
        Event::create(['customer_id' => $customer->id, 'title' => 'Visible Event', 'event_type' => 'wedding', 'host_name' => 'Host']);

        $this->actingAs($admin)->get(route('events.index', ['search' => 'absent']))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('events', [])
            ->where('hasEvents', true)
            ->where('isFiltered', true));
        $this->actingAs($admin)->get(route('admin.customers.index', ['search' => 'absent']))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Customers')
            ->where('customers', [])
            ->where('hasCustomers', true));
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Payments')
            ->where('hasClients', true));
    }
}
