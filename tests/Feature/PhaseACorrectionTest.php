<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationEntitlement;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseACorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_provisions_multiple_independently_configured_entitlements_without_a_blank_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $small = $this->package('51–100', 51, 100, 80);
        $large = $this->package('151–200', 151, 200, 180);

        $this->actingAs($admin)->post(route('admin.clients.store'), [
            'name' => 'Ramy', 'phone' => '555',
            'entitlements' => [
                ['event_package_id' => $small->id, 'exact_guest_capacity' => 80],
                ['event_package_id' => $large->id, 'exact_guest_capacity' => 180],
            ],
            'primary_name' => 'Ramy Primary', 'primary_email' => 'ramy@example.test', 'primary_password' => 'safe-password',
        ])->assertRedirect(route('admin.customers.index'));

        $customer = Customer::where('name', 'Ramy')->sole();
        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseHas('invitation_entitlements', ['customer_id' => $customer->id, 'event_package_id' => $small->id, 'exact_guest_capacity' => 80, 'status' => 'available']);
        $this->assertDatabaseHas('invitation_entitlements', ['customer_id' => $customer->id, 'event_package_id' => $large->id, 'exact_guest_capacity' => 180, 'status' => 'available']);
        $this->assertSame(['allowed_events' => 2, 'used_events' => 0, 'remaining_events' => 2, 'can_create_event' => true], $customer->allowanceSummary());
    }

    public function test_admin_can_add_an_entitlement_and_must_explicitly_start_the_selected_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $small = $this->package('51–100', 51, 100, 80);
        $large = $this->package('151–200', 151, 200, 180);
        $customer = Customer::create(['name' => 'Ramy']);
        $first = InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $small->id, 'exact_guest_capacity' => 80]);

        $this->actingAs($admin)->post(route('admin.customers.invitation-entitlements.store', $customer), ['event_package_id' => $large->id, 'exact_guest_capacity' => 180])->assertRedirect();
        $second = $customer->invitationEntitlements()->where('event_package_id', $large->id)->sole();
        $this->assertSame(2, $customer->fresh()->allowanceSummary()['remaining_events']);

        $this->actingAs($admin)->post(route('admin.customers.invitations.start', $customer))->assertSessionHasErrors('entitlement_id');
        $this->actingAs($admin)->post(route('admin.customers.invitations.start', $customer), ['entitlement_id' => $second->id])->assertRedirect();
        $event = Event::where('customer_id', $customer->id)->sole();
        $this->assertSame($large->id, $event->event_package_id);
        $this->assertSame(180, $event->guest_capacity);
        $this->assertTrue($first->fresh()->isAvailable());
    }

    public function test_customer_with_multiple_entitlements_must_choose_one_before_an_event_is_created(): void
    {
        [$customer, $owner, $small, $large] = $this->customerWithTwoEntitlements();
        $second = $customer->invitationEntitlements()->where('event_package_id', $large->id)->sole();

        $this->actingAs($owner)->get(route('events.start'))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Start')->has('entitlements', 2)->where('entitlements.0.exact_guest_capacity', 80)->where('entitlements.1.exact_guest_capacity', 180));
        $this->assertDatabaseCount('events', 0);
        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true])->assertSessionHasErrors('entitlement_id');
        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true, 'entitlement_id' => $second->id])->assertRedirect();

        $event = Event::where('customer_id', $customer->id)->sole();
        $this->assertSame($large->id, $event->event_package_id);
        $this->assertSame(180, $event->guest_capacity);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $owner->id]);
    }

    public function test_first_login_guides_a_new_customer_to_choice_without_claiming_and_returning_customer_goes_dashboard(): void
    {
        [$customer, $owner, $small] = $this->customerWithTwoEntitlements();
        $owner->update(['password' => Hash::make('safe-password')]);

        $this->post(route('login'), ['email' => $owner->email, 'password' => 'safe-password'])->assertRedirect(route('events.start'));
        $this->assertDatabaseCount('events', 0);

        auth()->logout();
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $small->id, 'guest_capacity' => 80, 'status' => 'draft']);
        $event->members()->attach($owner, ['role' => 'editor']);
        $this->post(route('login'), ['email' => $owner->email, 'password' => 'safe-password'])->assertRedirect(route('dashboard'));
    }

    public function test_customer_setup_persists_wedding_or_engagement_before_design_and_information_does_not_repeat_type(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $customer = Customer::create(['name' => 'Setup Customer']);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $package = EventPackage::query()->firstOrFail();
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'guest_capacity' => $package->minimum_guests, 'status' => 'draft']);
        $event->members()->attach($owner, ['role' => 'editor']);
        $template = Template::where('slug', 'romantic-floral')->sole();

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['event_type' => 'birthday', 'template_id' => $template->id])->assertSessionHasErrors('event_type');
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'design']), ['event_type' => 'wedding', 'template_id' => $template->id])->assertRedirect(route('events.setup', ['event' => $event, 'step' => 2]));
        $this->assertSame('wedding', $event->fresh()->event_type);

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'information']), [
            'title' => 'Ava and Leo', 'host_name' => 'Ava', 'main_date' => '2027-10-12', 'start_time' => '16:00', 'event_timezone' => 'Asia/Beirut',
        ])->assertRedirect(route('events.builder', $event));
        $this->assertSame('wedding', $event->fresh()->event_type);
    }

    public function test_claimed_entitlements_cannot_be_edited_through_the_available_slot_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = $this->package('51–100', 51, 100, 80);
        $customer = Customer::create(['name' => 'Claimed']);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'guest_capacity' => 80]);
        $entitlement = InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 80, 'status' => 'claimed', 'claimed_event_id' => $event->id, 'claimed_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.customers.invitation-entitlements.update', [$customer, $entitlement]), ['event_package_id' => $package->id, 'exact_guest_capacity' => 90])->assertStatus(422);
        $this->assertSame(80, $entitlement->fresh()->exact_guest_capacity);
    }

    private function customerWithTwoEntitlements(): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $small = $this->package('51–100', 51, 100, 80);
        $large = $this->package('151–200', 151, 200, 180);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $small->id, 'exact_guest_capacity' => 80]);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $large->id, 'exact_guest_capacity' => 180]);

        return [$customer, $owner, $small, $large];
    }

    private function package(string $name, int $minimum, int $maximum, int $price): EventPackage
    {
        return EventPackage::create(['name' => $name.' '.uniqid(), 'minimum_guests' => $minimum, 'maximum_guests' => $maximum, 'price' => $price, 'is_active' => true]);
    }
}
