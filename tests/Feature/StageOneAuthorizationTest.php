<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\Payment;
use App\Models\User;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageOneAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_all_events_and_manage_packages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $this->actingAs($admin)->get(route('events.show', $event))->assertOk();
        $this->actingAs($admin)->post(route('admin.packages.store'), ['name'=>'Small','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true])->assertRedirect();
        $this->assertDatabaseHas('event_packages', ['name' => 'Small']);
    }

    public function test_customer_cannot_access_another_customers_event_and_can_have_multiple_events(): void
    {
        $first = Customer::create(['name' => 'First']); $second = Customer::create(['name' => 'Second']);
        $one = User::factory()->create(['role'=>'customer','customer_id'=>$first->id]); $two = User::factory()->create(['role'=>'customer','customer_id'=>$second->id]);
        $firstEvent = Event::create(['customer_id'=>$first->id,'title'=>'First event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        $secondEvent = Event::create(['customer_id'=>$first->id,'title'=>'Second event','event_type'=>'birthday','host_name'=>'Host','status'=>'draft']);
        $foreign = Event::create(['customer_id'=>$second->id,'title'=>'Private','event_type'=>'birthday','host_name'=>'Host','status'=>'draft']);
        $firstEvent->members()->attach($one, ['role'=>'owner']); $secondEvent->members()->attach($one, ['role'=>'owner']); $foreign->members()->attach($two, ['role'=>'owner']);
        $this->actingAs($one)->get(route('events.show', $foreign))->assertForbidden();
        $this->actingAs($one)->get(route('events.index'))->assertOk();
        $this->assertCount(2, $one->managedEvents);
    }

    public function test_support_and_customers_cannot_access_admin_routes(): void
    {
        $support = User::factory()->create(['role'=>'support']); $customer = User::factory()->create(['role'=>'customer']);
        $this->actingAs($support)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.customers.index'))->assertForbidden();
    }

    public function test_admin_can_record_and_confirm_manual_payment(): void
    {
        $admin = User::factory()->create(['role'=>'admin']); $customer = Customer::create(['name'=>'Customer']); $package=EventPackage::create(['name'=>'Small','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id'=>$customer->id,'event_package_id'=>$package->id,'original_amount'=>25,'discount'=>0,'paid_amount'=>25])->assertRedirect();
        $paymentId = \App\Models\Payment::firstOrFail()->id;
        $this->actingAs($admin)->patch(route('admin.payments.confirm', $paymentId))->assertRedirect();
        $this->assertDatabaseHas('payments', ['id'=>$paymentId,'status'=>'confirmed']);
    }

    public function test_admin_searches_customers_users_events_and_payments_from_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Searchable Customer', 'email' => 'contact@example.test', 'phone' => '555-0100']);
        $member = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'name' => 'Searchable Member', 'email' => 'member@example.test']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Searchable Celebration', 'event_type' => 'wedding', 'host_name' => 'Search Host', 'venue' => 'Search Hall', 'status' => 'approved']);
        $event->members()->attach($member, ['role' => 'owner']);
        Payment::create(['customer_id'=>$customer->id,'event_id'=>$event->id,'original_amount'=>25,'discount'=>0,'final_amount'=>25,'paid_amount'=>25,'reference'=>'SEARCH-REF','status'=>'confirmed']);

        $this->actingAs($admin)->get(route('admin.customers.index', ['search' => '555-0100']))->assertSee('Searchable Customer');
        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Searchable Customer']))->assertSee('Searchable Member');
        $this->actingAs($admin)->get(route('events.index', ['search' => 'Search Hall']))->assertSee('Searchable Celebration');
        $this->actingAs($admin)->get(route('admin.payments.index', ['search' => 'SEARCH-REF']))->assertSee('SEARCH-REF');
    }

    public function test_event_and_package_filters_return_only_matching_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        Event::create(['customer_id'=>$customer->id,'title'=>'Wedding draft','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        Event::create(['customer_id'=>$customer->id,'title'=>'Birthday approved','event_type'=>'birthday','host_name'=>'Host','status'=>'approved']);
        EventPackage::create(['name'=>'Active Package','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        EventPackage::create(['name'=>'Inactive Package','minimum_guests'=>51,'maximum_guests'=>100,'price'=>55,'is_active'=>false]);

        $this->actingAs($admin)->get(route('events.index', ['event_type'=>'birthday', 'status'=>'approved']))->assertSee('Birthday approved')->assertDontSee('Wedding draft');
        $this->actingAs($admin)->get(route('admin.packages.index', ['active'=>'inactive']))->assertSee('Inactive Package')->assertDontSee('Active Package');
    }

    public function test_customer_event_search_never_exposes_another_customers_data(): void
    {
        $first = Customer::create(['name'=>'First']); $second = Customer::create(['name'=>'Second']);
        $user = User::factory()->create(['role'=>'customer','customer_id'=>$first->id]); $other = User::factory()->create(['role'=>'customer','customer_id'=>$second->id]);
        $visible = Event::create(['customer_id'=>$first->id,'title'=>'My Searchable Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        $hidden = Event::create(['customer_id'=>$second->id,'title'=>'Private Searchable Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);
        $visible->members()->attach($user, ['role'=>'owner']); $hidden->members()->attach($other, ['role'=>'owner']);
        $this->actingAs($user)->get(route('events.index', ['search'=>'Searchable']))->assertSee('My Searchable Event')->assertDontSee('Private Searchable Event');
    }

    public function test_event_details_response_contains_complete_existing_event_data_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Detail Customer', 'contact_name' => 'Detail Contact', 'email' => 'detail@example.test', 'phone' => '555-0199']);
        $package = EventPackage::create(['name' => 'Detail Package', 'minimum_guests' => 51, 'maximum_guests' => 100, 'price' => 55, 'is_active' => true]);
        $member = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'name' => 'Detail Member']);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Complete Detail Event', 'event_type' => 'wedding', 'host_name' => 'Primary Host', 'second_host_name' => 'Partner Host', 'description' => 'Detail description', 'main_date' => '2026-12-10', 'start_time' => '18:00', 'end_time' => '23:00', 'venue' => 'Detail Hall', 'address' => '1 Detail Street', 'location_url' => 'https://example.test/location', 'rsvp_deadline' => '2026-11-20', 'status' => 'approved']);
        $event->members()->attach($member, ['role' => 'owner']);
        Payment::create(['customer_id' => $customer->id, 'event_id' => $event->id, 'event_package_id' => $package->id, 'original_amount' => 55, 'discount' => 5, 'final_amount' => 50, 'paid_amount' => 20, 'status' => 'confirmed']);

        $this->actingAs($admin)->get(route('events.show', $event))->assertOk()->assertSee(['Complete Detail Event', 'Partner Host', 'Detail description', 'Detail Hall', 'Detail Customer', 'Detail Contact', 'detail@example.test', '555-0199', 'Detail Package', 'Detail Member', 'remaining_amount']);
    }

    public function test_authorized_event_member_can_view_details_without_customer_contact_information(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'email' => 'internal@example.test']);
        $member = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Member Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);
        $event->members()->attach($member, ['role' => 'editor']);

        $this->actingAs($member)->get(route('events.show', $event))->assertOk()->assertSee('Member Event')->assertDontSee('internal@example.test');
    }

    public function test_admin_can_edit_all_stage_one_event_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $package = EventPackage::create(['name'=>'Assigned','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'title'=>'Old','event_type'=>'birthday','host_name'=>'Old Host','status'=>'draft']);

        $this->actingAs($admin)->put(route('events.update', $event), ['customer_id'=>$customer->id,'event_package_id'=>$package->id,'title'=>'Updated Event','event_type'=>'wedding','host_name'=>'Primary Host','second_host_name'=>'Second Host','description'=>'Updated description','main_date'=>'2026-12-20','start_time'=>'18:00','end_time'=>'23:00','venue'=>'Updated Venue','address'=>'10 Updated Street','location_url'=>'https://example.test/updated','rsvp_deadline'=>'2026-12-10','status'=>'approved'])->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('events', ['id'=>$event->id,'event_package_id'=>$package->id,'title'=>'Updated Event','event_type'=>'wedding','host_name'=>'Primary Host','second_host_name'=>'Second Host','venue'=>'Updated Venue','address'=>'10 Updated Street','location_url'=>'https://example.test/updated','status'=>'approved']);
    }

    public function test_customer_can_edit_permitted_content_but_cannot_change_package_or_admin_only_status(): void
    {
        $customer = Customer::create(['name'=>'Customer']);
        $member = User::factory()->create(['role'=>'customer','customer_id'=>$customer->id]);
        $assigned = EventPackage::create(['name'=>'Assigned','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        $other = EventPackage::create(['name'=>'Other','minimum_guests'=>51,'maximum_guests'=>100,'price'=>55,'is_active'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$assigned->id,'title'=>'Original','event_type'=>'birthday','host_name'=>'Host','status'=>'draft']);
        $event->members()->attach($member, ['role'=>'editor']);
        $payload = ['customer_id'=>$customer->id,'event_package_id'=>$other->id,'title'=>'Customer Update','event_type'=>'birthday','host_name'=>'Host','second_host_name'=>'','description'=>'Updated by member','main_date'=>'2026-12-20','start_time'=>'10:00','end_time'=>'12:00','venue'=>'Venue','address'=>'Address','location_url'=>'https://example.test/location','rsvp_deadline'=>'2026-12-10','status'=>'submitted'];

        $this->actingAs($member)->put(route('events.update', $event), $payload)->assertRedirect(route('events.show', $event));
        $this->assertDatabaseHas('events', ['id'=>$event->id,'title'=>'Customer Update','event_package_id'=>$assigned->id,'status'=>'submitted']);

        $payload['status'] = 'approved';
        $this->actingAs($member)->put(route('events.update', $event), $payload)->assertSessionHasErrors('status');
        $this->assertDatabaseHas('events', ['id'=>$event->id,'status'=>'submitted','event_package_id'=>$assigned->id]);
    }

    public function test_unauthorized_customer_cannot_edit_another_customers_event(): void
    {
        $first = Customer::create(['name'=>'First']); $second = Customer::create(['name'=>'Second']);
        $user = User::factory()->create(['role'=>'customer','customer_id'=>$first->id]);
        $event = Event::create(['customer_id'=>$second->id,'title'=>'Private','event_type'=>'birthday','host_name'=>'Host','status'=>'draft']);

        $this->actingAs($user)->put(route('events.update', $event), ['title'=>'Attempt'])->assertForbidden();
    }

    public function test_packages_are_returned_in_guest_capacity_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        EventPackage::create(['name'=>'Large package','minimum_guests'=>201,'maximum_guests'=>250,'price'=>130,'is_active'=>true]);
        EventPackage::create(['name'=>'Small package','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        EventPackage::create(['name'=>'Medium package','minimum_guests'=>51,'maximum_guests'=>100,'price'=>55,'is_active'=>true]);

        $this->actingAs($admin)->get(route('admin.packages.index'))->assertSeeInOrder(['Small package', 'Medium package', 'Large package']);
    }

    public function test_financial_overview_totals_and_remaining_balance_are_calculated_from_existing_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name'=>'Customer']);
        Payment::create(['customer_id'=>$customer->id,'original_amount'=>100,'discount'=>10,'final_amount'=>90,'paid_amount'=>40,'status'=>'pending']);
        Payment::create(['customer_id'=>$customer->id,'original_amount'=>50,'discount'=>0,'final_amount'=>50,'paid_amount'=>60,'status'=>'confirmed']);

        $version = app(HandleInertiaRequests::class)->version(request());
        $response = $this->actingAs($admin)->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get(route('admin.payments.index'));
        $response->assertOk()->assertJsonPath('props.summary.total_final_amount', 140)->assertJsonPath('props.summary.total_paid_amount', 100)->assertJsonPath('props.summary.total_remaining_amount', 50)->assertJsonPath('props.summary.pending_count', 1)->assertJsonPath('props.summary.confirmed_count', 1);
    }

    public function test_customer_cannot_access_another_customers_financial_summary(): void
    {
        $first = Customer::create(['name'=>'First']); $second = Customer::create(['name'=>'Second']);
        $user = User::factory()->create(['role'=>'customer','customer_id'=>$first->id]); $other = User::factory()->create(['role'=>'customer','customer_id'=>$second->id]);
        $privateEvent = Event::create(['customer_id'=>$second->id,'title'=>'Private Financial Event','event_type'=>'birthday','host_name'=>'Host','status'=>'draft']);
        $privateEvent->members()->attach($other, ['role'=>'owner']);
        Payment::create(['customer_id'=>$second->id,'event_id'=>$privateEvent->id,'original_amount'=>100,'discount'=>0,'final_amount'=>100,'paid_amount'=>25,'status'=>'pending']);

        $this->actingAs($user)->get(route('events.show', $privateEvent))->assertForbidden();
    }
}
