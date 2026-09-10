<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationEntitlement;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use App\Models\Payment;
use App\Models\User;
use App\Support\AdminDashboardAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOperationsPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_bounded_operational_upcoming_and_attention_data(): void
    {
        $exhausted = Customer::create(['name' => 'At allowance', 'allowed_events' => 1]);
        $event = Event::create(['customer_id' => $exhausted->id, 'title' => 'Upcoming live', 'event_type' => 'wedding', 'host_name' => 'Host', 'main_date' => today()->addDay(), 'guest_capacity' => 12, 'status' => 'published']);
        EventPublication::create(['event_id' => $event->id, 'version' => 1, 'snapshot' => [], 'snapshot_hash' => str_repeat('b', 64), 'published_at' => now()]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 2]);
        $party->rsvp->update(['status' => 'attending', 'submitted_at' => now()]);
        Event::create(['customer_id' => Customer::create(['name' => 'Disabled'])->id, 'title' => 'Paused', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'disabled']);

        $analytics = app(AdminDashboardAnalytics::class)->data();

        $upcoming = collect($analytics['upcoming'])->firstWhere('id', $event->id);
        $this->assertSame(1, $upcoming['families']);
        $this->assertSame(1, $upcoming['responded_families']);
        $this->assertSame(100, $upcoming['response_rate']);
        $this->assertTrue(collect($analytics['attention'])->contains(fn (array $item) => $item['kind'] === 'customer' && $item['id'] === $exhausted->id));
        $this->assertTrue(collect($analytics['attention'])->contains(fn (array $item) => $item['title'] === 'Paused'));
    }

    public function test_admin_customer_detail_exposes_operational_event_and_finance_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Operations Customer', 'allowed_events' => 2]);
        $package = EventPackage::create(['name' => 'Operations Package', 'minimum_guests' => 1, 'maximum_guests' => 20, 'price' => 20, 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Operational event', 'event_type' => 'birthday', 'host_name' => 'Host', 'guest_capacity' => 10, 'status' => 'published']);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 10, 'status' => InvitationEntitlement::CLAIMED, 'claimed_event_id' => $event->id, 'claimed_at' => now()]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 3]);
        $party->rsvp->update(['status' => 'not_attending', 'submitted_at' => now()]);
        $payment = Payment::create(['customer_id' => $customer->id, 'event_id' => $event->id, 'original_amount' => 100, 'final_amount' => 90, 'paid_amount' => 0, 'status' => 'unpaid']);
        $payment->transactions()->create(['amount' => 40, 'status' => 'confirmed']);
        $payment->refreshTotals();

        $this->actingAs($admin)->get(route('admin.customers.show', $customer))->assertInertia(fn (Assert $page) => $page
            ->where('allowance.used_events', 1)
            ->where('events.0.invitation_status', 'live')
            ->where('events.0.allocated_capacity', 3)
            ->where('events.0.families_count', 1)
            ->where('events.0.responded_families_count', 1)
            ->where('events.0.finance.final_amount', 90)
            ->where('events.0.finance.remaining_amount', 50));
    }
}
