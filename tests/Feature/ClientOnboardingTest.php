<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_client_primary_login_package_and_empty_setup_shell_together(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = EventPackage::create(['name' => '201–250 Guests', 'minimum_guests' => 201, 'maximum_guests' => 250, 'price' => 130, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.clients.store'), [
            'name' => 'Acme Family', 'phone' => '555', 'guest_capacity' => 220, 'allowed_events' => 1,
            'primary_name' => 'Maya Client', 'primary_email' => 'maya@example.test', 'primary_password' => 'safe-password',
        ])->assertRedirect();

        $customer = Customer::where('name', 'Acme Family')->firstOrFail();
        $user = User::where('email', 'maya@example.test')->firstOrFail();
        $event = Event::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('primary', $user->customer_account_role);
        $this->assertTrue(Hash::check('safe-password', $user->password));
        $this->assertSame('Maya Client', $customer->contact_name);
        $this->assertSame('maya@example.test', $customer->email);
        $this->assertNull($event->title);
        $this->assertSame($package->id, $event->event_package_id);
        $this->assertSame(220, $event->guest_capacity);
        $this->assertSame(220, $event->effectiveGuestCapacity());
        $this->assertSame('130.00', $event->package->price);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $user->id, 'role' => 'owner']);
        $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertInertia(fn (Assert $page) => $page
            ->where('events.0.guest_capacity', 220)
            ->where('events.0.package.name', '201–250 Guests')
            ->where('events.0.finance.has_record', false));
    }

    public function test_client_cannot_have_more_than_two_customer_login_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Client']);
        User::factory()->create(['customer_id' => $customer->id, 'role' => 'customer']);
        User::factory()->create(['customer_id' => $customer->id, 'role' => 'customer']);

        $this->actingAs($admin)->post(route('admin.customers.second-login.store', $customer), ['name' => 'Third', 'email' => 'third@example.test', 'password' => 'safe-password'])
            ->assertSessionHasErrors('customer_id');
        $this->assertDatabaseMissing('users', ['email' => 'third@example.test']);
    }

    public function test_clients_list_exposes_safe_single_event_summary_and_does_not_choose_from_multiple_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = EventPackage::create(['name' => '50 guests', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 100, 'is_active' => true]);
        $one = Customer::create(['name' => 'One Event']);
        $primary = User::factory()->create(['customer_id'=>$one->id, 'role'=>'customer', 'name'=>'Primary', 'email'=>'primary@example.test', 'customer_account_role'=>'primary']);
        User::factory()->create(['customer_id'=>$one->id, 'role'=>'customer', 'name'=>'Second', 'email'=>'second@example.test', 'customer_account_role'=>'secondary']);
        Event::create(['customer_id'=>$one->id,'event_package_id'=>$package->id,'title'=>'Only Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        $many = Customer::create(['name' => 'Multiple Events']);
        Event::create(['customer_id'=>$many->id,'event_package_id'=>$package->id,'title'=>'First Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        Event::create(['customer_id'=>$many->id,'event_package_id'=>$package->id,'title'=>'Second Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        $many->forceFill(['created_at' => now()->addMinute()])->save();

        $this->actingAs($admin)->get(route('admin.customers.index'))->assertInertia(fn (Assert $page) => $page->component('Admin/Customers')
            ->where('customers.0.event_count', 2)
            ->where('customers.0.event', null)
            ->where('customers.1.primary_login.email', 'primary@example.test')
            ->where('customers.1.second_login.email', 'second@example.test')
            ->where('customers.1.event.title', 'Only Event'));
    }

    public function test_guest_capacity_requires_exactly_one_active_package_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        EventPackage::create(['name'=>'1–50','minimum_guests'=>1,'maximum_guests'=>50,'price'=>100,'is_active'=>true]);
        $payload = ['name'=>'Client','guest_capacity'=>51,'allowed_events'=>1,'primary_name'=>'Primary','primary_email'=>'primary@example.test','primary_password'=>'safe-password'];
        $this->actingAs($admin)->post(route('admin.clients.store'), $payload)->assertSessionHasErrors('guest_capacity');
        EventPackage::create(['name'=>'40–60','minimum_guests'=>40,'maximum_guests'=>60,'price'=>120,'is_active'=>true]);
        $payload['guest_capacity'] = 45;
        $this->actingAs($admin)->post(route('admin.clients.store'), $payload)->assertSessionHasErrors('guest_capacity');
    }

    public function test_non_admins_cannot_create_clients(): void
    {
        $payload = ['name'=>'Client','guest_capacity'=>20,'allowed_events'=>1,'primary_name'=>'Primary','primary_email'=>'primary@example.test','primary_password'=>'safe-password'];
        $this->actingAs(User::factory()->create(['role'=>'customer']))->post(route('admin.clients.store'), $payload)->assertForbidden();
    }

    public function test_legacy_event_uses_its_package_maximum_when_no_exact_capacity_exists(): void
    {
        $package = EventPackage::create(['name'=>'201–250','minimum_guests'=>201,'maximum_guests'=>250,'price'=>130,'is_active'=>true]);
        $event = Event::create(['customer_id'=>Customer::create(['name'=>'Legacy'])->id,'event_package_id'=>$package->id,'title'=>'Legacy','event_type'=>'wedding','host_name'=>'Host']);
        $this->assertNull($event->guest_capacity);
        $this->assertSame(250, $event->effectiveGuestCapacity());
    }

    public function test_customer_cannot_increase_purchased_guest_capacity_through_a_crafted_event_update(): void
    {
        $customer = Customer::create(['name'=>'Client']);
        $owner = User::factory()->create(['role'=>'customer','customer_id'=>$customer->id]);
        $package = EventPackage::create(['name'=>'201–250','minimum_guests'=>201,'maximum_guests'=>250,'price'=>130,'is_active'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$package->id,'guest_capacity'=>220,'title'=>'Event','event_type'=>'wedding','host_name'=>'Host']);
        $event->members()->attach($owner, ['role'=>'owner']);

        $this->actingAs($owner)->put(route('events.update', $event), ['guest_capacity'=>250,'title'=>'Updated Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft'])->assertRedirect();
        $this->assertSame(220, $event->fresh()->guest_capacity);
    }

    public function test_live_setup_save_creates_next_immutable_version_without_a_second_publish_action(): void
    {
        $customer = Customer::create(['name' => 'Client']);
        $owner = User::factory()->create(['customer_id' => $customer->id, 'role' => 'customer']);
        $package = EventPackage::create(['name' => '50 guests', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 100, 'is_active' => true]);
        $template = \App\Models\Template::create(['name'=>'Romantic','slug'=>'romantic','component_key'=>'romantic-floral','default_settings'=>[], 'is_active'=>true, 'is_customer_selectable'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$package->id,'template_id'=>$template->id,'title'=>'Original','event_type'=>'wedding','host_name'=>'Maya','main_date'=>'2027-01-01','start_time'=>'16:00','event_timezone'=>'America/New_York']);
        $event->members()->attach($owner, ['role'=>'owner']);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'details']), ['event_type'=>'wedding','title'=>'Changed','host_name'=>'Maya','second_host_name'=>null,'description'=>null])->assertRedirect();
        $this->assertDatabaseCount('event_publications', 2);
        $this->assertSame('Changed', $event->fresh()->latestPublication()->snapshot['event']['title']);
    }

    public function test_live_meal_changes_create_the_next_immutable_version_in_the_same_action(): void
    {
        $customer = Customer::create(['name' => 'Client']);
        $owner = User::factory()->create(['customer_id' => $customer->id, 'role' => 'customer']);
        $package = EventPackage::create(['name' => '50 guests', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 100, 'is_active' => true]);
        $template = \App\Models\Template::create(['name'=>'Romantic','slug'=>'romantic-meal','component_key'=>'romantic-floral','default_settings'=>[], 'is_active'=>true, 'is_customer_selectable'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$package->id,'template_id'=>$template->id,'title'=>'Original','event_type'=>'wedding','host_name'=>'Maya','main_date'=>'2027-01-01','start_time'=>'16:00','event_timezone'=>'America/New_York']);
        $event->members()->attach($owner, ['role'=>'owner']);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();

        $this->actingAs($owner)->post(route('events.meals.store', $event), ['name'=>'Chicken'])->assertRedirect();
        $this->assertDatabaseCount('event_publications', 2);
        $this->assertSame('Chicken', $event->fresh()->latestPublication()->snapshot['rsvp']['meals'][0]['name']);
    }
}
