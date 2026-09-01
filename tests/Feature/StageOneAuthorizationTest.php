<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Coupon;
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

    public function test_customers_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create(['role'=>'customer']);
        $this->actingAs($customer)->get(route('admin.customers.index'))->assertForbidden();
    }

    public function test_admin_can_record_and_confirm_manual_payment(): void
    {
        $admin = User::factory()->create(['role'=>'admin']); $customer = Customer::create(['name'=>'Customer']); $package=EventPackage::create(['name'=>'Small','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Payment Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id'=>$customer->id,'event_id' => $event->id,'event_package_id'=>$package->id,'original_amount'=>25,'discount'=>0,'paid_amount'=>25])->assertRedirect();
        $paymentId = \App\Models\Payment::firstOrFail()->id;
        $this->actingAs($admin)->patch(route('admin.payments.confirm', $paymentId))->assertRedirect();
        $this->assertDatabaseHas('payments', ['id'=>$paymentId,'status'=>'paid']);
        $this->assertDatabaseHas('payment_transactions', ['payment_id' => $paymentId, 'amount' => 25]);
    }

    public function test_admin_searches_customers_events_and_payments_from_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Searchable Customer', 'email' => 'contact@example.test', 'phone' => '555-0100']);
        $member = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id, 'name' => 'Searchable Member', 'email' => 'member@example.test']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Searchable Celebration', 'event_type' => 'wedding', 'host_name' => 'Search Host', 'venue' => 'Search Hall', 'status' => 'approved']);
        $event->members()->attach($member, ['role' => 'owner']);
        Payment::create(['customer_id'=>$customer->id,'event_id'=>$event->id,'original_amount'=>25,'discount'=>0,'final_amount'=>25,'paid_amount'=>25,'reference'=>'SEARCH-REF','status'=>'confirmed']);

        $this->actingAs($admin)->get(route('admin.customers.index', ['search' => '555-0100']))->assertSee('Searchable Customer');
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

        $this->actingAs($admin)->put(route('events.update', $event), ['customer_id'=>$customer->id,'event_package_id'=>$package->id,'title'=>'Updated Event','event_type'=>'wedding','host_name'=>'Primary Host','second_host_name'=>'Second Host','description'=>'Updated description','main_date'=>'2026-12-20','start_time'=>'18:00','end_time'=>'23:00','venue'=>'Updated Venue','address'=>'10 Updated Street','location_url'=>'https://example.test/updated','rsvp_deadline'=>'2026-12-10','status'=>'draft'])->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('events', ['id'=>$event->id,'event_package_id'=>$package->id,'title'=>'Updated Event','event_type'=>'wedding','host_name'=>'Primary Host','second_host_name'=>'Second Host','venue'=>'Updated Venue','address'=>'10 Updated Street','location_url'=>'https://example.test/updated','status'=>'draft']);
    }

    public function test_customer_can_edit_permitted_content_but_cannot_change_package_or_admin_only_status(): void
    {
        $customer = Customer::create(['name'=>'Customer']);
        $member = User::factory()->create(['role'=>'customer','customer_id'=>$customer->id]);
        $assigned = EventPackage::create(['name'=>'Assigned','minimum_guests'=>1,'maximum_guests'=>50,'price'=>25,'is_active'=>true]);
        $other = EventPackage::create(['name'=>'Other','minimum_guests'=>51,'maximum_guests'=>100,'price'=>55,'is_active'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$assigned->id,'title'=>'Original','event_type'=>'birthday','host_name'=>'Host','status'=>'draft']);
        $event->members()->attach($member, ['role'=>'editor']);
        $payload = ['customer_id'=>$customer->id,'event_package_id'=>$other->id,'title'=>'Customer Update','event_type'=>'birthday','host_name'=>'Host','second_host_name'=>'','description'=>'Updated by member','main_date'=>'2026-12-20','start_time'=>'10:00','end_time'=>'12:00','venue'=>'Venue','address'=>'Address','location_url'=>'https://example.test/location','rsvp_deadline'=>'2026-12-10','status'=>'draft'];

        $this->actingAs($member)->put(route('events.update', $event), $payload)->assertRedirect(route('events.show', $event));
        $this->assertDatabaseHas('events', ['id'=>$event->id,'title'=>'Customer Update','event_package_id'=>$assigned->id,'status'=>'draft']);

        $payload['status'] = 'approved';
        $this->actingAs($member)->put(route('events.update', $event), $payload)->assertSessionHasErrors('status');
        $this->assertDatabaseHas('events', ['id'=>$event->id,'status'=>'draft','event_package_id'=>$assigned->id]);
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
        Payment::create(['customer_id'=>$customer->id,'original_amount'=>100,'discount'=>10,'final_amount'=>90,'paid_amount'=>40,'status'=>'partially_paid']);
        Payment::create(['customer_id'=>$customer->id,'original_amount'=>50,'discount'=>0,'final_amount'=>50,'paid_amount'=>60,'status'=>'paid']);

        $version = app(HandleInertiaRequests::class)->version(request());
        $response = $this->actingAs($admin)->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get(route('admin.payments.index'));
        $response->assertOk()->assertJsonPath('props.summary.total_final_amount', 140)->assertJsonPath('props.summary.total_paid_amount', 100)->assertJsonPath('props.summary.total_remaining_amount', 50)->assertJsonPath('props.summary.partially_paid_count', 1)->assertJsonPath('props.summary.paid_count', 1);
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

    public function test_admin_can_record_full_and_partial_payments_with_transaction_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Financial Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 100, 'paid_amount' => 100, 'payment_method' => 'cash'])->assertRedirect();
        $full = Payment::where('event_id', $event->id)->firstOrFail();
        $this->assertSame('paid', $full->status);
        $this->assertSame('100.00', $full->paid_amount);
        $this->assertCount(1, $full->transactions);

        $secondEvent = Event::create(['customer_id' => $customer->id, 'title' => 'Partial Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);
        $partial = Payment::create(['customer_id' => $customer->id, 'event_id' => $secondEvent->id, 'original_amount' => 100, 'discount' => 0, 'final_amount' => 100, 'paid_amount' => 0, 'status' => 'unpaid']);
        $this->actingAs($admin)->post(route('admin.payments.transactions.store', $partial), ['amount' => 40, 'payment_method' => 'cash'])->assertRedirect();
        $partial->refresh();
        $this->assertSame('partially_paid', $partial->status);
        $this->assertSame('40.00', $partial->paid_amount);
        $this->actingAs($admin)->post(route('admin.payments.transactions.store', $partial), ['amount' => 60, 'payment_method' => 'bank transfer'])->assertRedirect();
        $partial->refresh();
        $this->assertSame('paid', $partial->status);
        $this->assertSame('100.00', $partial->paid_amount);
        $this->assertSame(0.0, $partial->remainingAmount());
        $this->assertCount(2, $partial->transactions);
    }

    public function test_unpaid_status_and_overpayment_protection_are_enforced(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $payment = Payment::create(['customer_id' => $customer->id, 'original_amount' => 100, 'discount' => 0, 'final_amount' => 100, 'paid_amount' => 0, 'status' => 'unpaid']);

        $this->assertSame('unpaid', $payment->status);
        $this->actingAs($admin)->post(route('admin.payments.transactions.store', $payment), ['amount' => 100.01])->assertSessionHasErrors('amount');
        $this->assertCount(0, $payment->transactions);
    }

    public function test_admin_can_apply_fixed_and_percentage_discounts_without_negative_final_amounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $payment = Payment::create(['customer_id' => $customer->id, 'original_amount' => 200, 'discount' => 0, 'final_amount' => 200, 'paid_amount' => 0, 'status' => 'unpaid']);

        $this->actingAs($admin)->put(route('admin.payments.discount.update', $payment), ['discount_type' => 'fixed', 'discount_value' => 25])->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'discount' => 25, 'final_amount' => 175, 'discount_type' => 'fixed', 'discount_value' => 25]);
        $this->actingAs($admin)->put(route('admin.payments.discount.update', $payment), ['discount_type' => 'percentage', 'discount_value' => 10])->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'discount' => 20, 'final_amount' => 180, 'discount_type' => 'percentage', 'discount_value' => 10]);
        $this->actingAs($admin)->put(route('admin.payments.discount.update', $payment), ['discount_type' => 'percentage', 'discount_value' => 101])->assertSessionHasErrors('discount_value');
    }

    public function test_admin_can_apply_valid_coupon_and_reject_inactive_or_expired_coupons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $payment = Payment::create(['customer_id' => $customer->id, 'original_amount' => 200, 'discount' => 0, 'final_amount' => 200, 'paid_amount' => 0, 'status' => 'unpaid']);
        $valid = Coupon::create(['code' => 'TENOFF', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_active' => true]);
        $expired = Coupon::create(['code' => 'EXPIRED', 'discount_type' => 'fixed', 'discount_value' => 20, 'is_active' => true, 'expires_at' => now()->subDay()]);
        $inactive = Coupon::create(['code' => 'INACTIVE', 'discount_type' => 'fixed', 'discount_value' => 20, 'is_active' => false]);

        $this->actingAs($admin)->put(route('admin.payments.coupon.update', $payment), ['coupon_id' => $valid->id])->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'coupon_id' => $valid->id, 'coupon_code' => 'TENOFF', 'discount' => 20, 'final_amount' => 180]);
        $this->actingAs($admin)->put(route('admin.payments.coupon.update', $payment), ['coupon_id' => $expired->id])->assertSessionHasErrors('coupon_id');
        $this->actingAs($admin)->put(route('admin.payments.coupon.update', $payment), ['coupon_id' => $inactive->id])->assertSessionHasErrors('coupon_id');
    }

    public function test_customer_cannot_add_payments_or_change_discounts_and_coupons(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $payment = Payment::create(['customer_id' => $customer->id, 'original_amount' => 100, 'discount' => 0, 'final_amount' => 100, 'paid_amount' => 0, 'status' => 'unpaid']);
        $coupon = Coupon::create(['code' => 'NOACCESS', 'discount_type' => 'fixed', 'discount_value' => 10, 'is_active' => true]);

        $this->actingAs($user)->post(route('admin.payments.transactions.store', $payment), ['amount' => 10])->assertForbidden();
        $this->actingAs($user)->put(route('admin.payments.discount.update', $payment), ['discount_type' => 'fixed', 'discount_value' => 10])->assertForbidden();
        $this->actingAs($user)->put(route('admin.payments.coupon.update', $payment), ['coupon_id' => $coupon->id])->assertForbidden();
    }

    public function test_authorized_customer_can_view_only_their_event_financial_summary_and_safe_history(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'My Financial Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $event->members()->attach($user, ['role' => 'owner']);
        $payment = Payment::create(['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 100, 'discount' => 0, 'final_amount' => 100, 'paid_amount' => 0, 'status' => 'unpaid']);
        $payment->transactions()->create(['amount' => 40, 'payment_method' => 'cash', 'reference' => 'SAFE-REF', 'notes' => 'Internal note', 'status' => 'confirmed']);
        $payment->refreshTotals();

        $this->actingAs($user)->get(route('events.show', $event))->assertOk()->assertSee(['partially_paid', 'SAFE-REF'])->assertDontSee('Internal note');
    }

    public function test_financial_record_selection_data_identifies_each_events_customer_and_assigned_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = Customer::create(['name' => 'First']);
        $second = Customer::create(['name' => 'Second']);
        $firstPackage = EventPackage::create(['name' => 'First Package', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $secondPackage = EventPackage::create(['name' => 'Second Package', 'minimum_guests' => 51, 'maximum_guests' => 100, 'price' => 55, 'is_active' => true]);
        $firstEvent = Event::create(['customer_id' => $first->id, 'event_package_id' => $firstPackage->id, 'title' => 'First Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $secondEvent = Event::create(['customer_id' => $second->id, 'event_package_id' => $secondPackage->id, 'title' => 'Second Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);

        $version = app(HandleInertiaRequests::class)->version(request());
        $response = $this->actingAs($admin)->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get(route('admin.payments.index'));
        $events = collect($response->json('props.events'));

        $this->assertSame([$firstEvent->id], $events->where('customer_id', $first->id)->pluck('id')->all());
        $this->assertSame([$secondEvent->id], $events->where('customer_id', $second->id)->pluck('id')->all());
        $this->assertSame($firstPackage->id, $events->firstWhere('id', $firstEvent->id)['package']['id']);
    }

    public function test_financial_record_rejects_stale_customer_event_and_package_combinations_and_derives_event_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = Customer::create(['name' => 'First']);
        $second = Customer::create(['name' => 'Second']);
        $assigned = EventPackage::create(['name' => 'Assigned', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $unrelated = EventPackage::create(['name' => 'Unrelated', 'minimum_guests' => 51, 'maximum_guests' => 100, 'price' => 55, 'is_active' => true]);
        $firstEvent = Event::create(['customer_id' => $first->id, 'event_package_id' => $assigned->id, 'title' => 'First Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $secondEvent = Event::create(['customer_id' => $second->id, 'event_package_id' => $assigned->id, 'title' => 'Second Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);
        $noPackageEvent = Event::create(['customer_id' => $first->id, 'title' => 'No Package Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $first->id, 'event_id' => $secondEvent->id, 'event_package_id' => $assigned->id, 'original_amount' => 25])->assertSessionHasErrors('event_id');
        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $first->id, 'event_id' => $firstEvent->id, 'event_package_id' => $unrelated->id, 'original_amount' => 25])->assertSessionHasErrors('event_package_id');
        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $first->id, 'event_id' => $noPackageEvent->id, 'event_package_id' => $unrelated->id, 'original_amount' => 25])->assertSessionHasErrors('event_package_id');

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $first->id, 'event_id' => $firstEvent->id, 'original_amount' => 25])->assertRedirect();
        $this->assertDatabaseHas('payments', ['customer_id' => $first->id, 'event_id' => $firstEvent->id, 'event_package_id' => $assigned->id]);
    }

    public function test_admin_can_create_a_financial_record_with_a_coupon_snapshot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $package = EventPackage::create(['name' => 'Package', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 200, 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Coupon Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $coupon = Coupon::create(['code' => 'SAVE10', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 200, 'coupon_id' => $coupon->id, 'paid_amount' => 50])->assertRedirect();
        $this->assertDatabaseHas('payments', ['event_id' => $event->id, 'coupon_id' => $coupon->id, 'coupon_code' => 'SAVE10', 'discount_type' => 'percentage', 'discount_value' => 10, 'discount' => 20, 'final_amount' => 180, 'paid_amount' => 50]);
    }

    public function test_admin_can_create_a_financial_record_with_a_manual_fixed_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Fixed Discount Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 200, 'discount_type' => 'fixed', 'discount_value' => 25])->assertRedirect();
        $this->assertDatabaseHas('payments', ['event_id' => $event->id, 'discount_type' => 'fixed', 'discount_value' => 25, 'discount' => 25, 'final_amount' => 175, 'coupon_id' => null]);
    }

    public function test_admin_can_create_a_financial_record_with_a_manual_percentage_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Percentage Discount Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 200, 'discount_type' => 'percentage', 'discount_value' => 10])->assertRedirect();
        $this->assertDatabaseHas('payments', ['event_id' => $event->id, 'discount_type' => 'percentage', 'discount_value' => 10, 'discount' => 20, 'final_amount' => 180, 'coupon_id' => null]);
    }

    public function test_financial_record_creation_rejects_an_invalid_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Invalid Coupon Event', 'event_type' => 'birthday', 'host_name' => 'Host', 'status' => 'draft']);
        $coupon = Coupon::create(['code' => 'INACTIVE-CREATE', 'discount_type' => 'fixed', 'discount_value' => 20, 'is_active' => false]);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 200, 'coupon_id' => $coupon->id])->assertSessionHasErrors('coupon_id');
        $this->assertDatabaseMissing('payments', ['event_id' => $event->id]);

        $validCoupon = Coupon::create(['code' => 'VALID-CREATE', 'discount_type' => 'fixed', 'discount_value' => 20, 'is_active' => true]);
        $secondEvent = Event::create(['customer_id' => $customer->id, 'title' => 'Mixed Discount Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $secondEvent->id, 'original_amount' => 200, 'coupon_id' => $validCoupon->id, 'discount_type' => 'fixed', 'discount_value' => 10])->assertSessionHasErrors('coupon_id');
        $this->assertDatabaseMissing('payments', ['event_id' => $secondEvent->id]);
    }

    public function test_initial_payment_is_limited_by_the_calculated_final_amount_during_creation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Initial Payment Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.payments.store'), ['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 100, 'discount_type' => 'fixed', 'discount_value' => 20, 'paid_amount' => 81])->assertSessionHasErrors('paid_amount');
        $this->assertDatabaseMissing('payments', ['event_id' => $event->id]);
    }
}
