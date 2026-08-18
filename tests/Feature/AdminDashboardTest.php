<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use App\Models\Payment;
use App\Models\User;
use App\Support\AdminDashboardAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_kpis_use_final_obligations_and_confirmed_transactions(): void
    {
        $customer = Customer::create(['name' => 'Analytics Customer']);
        $overpaid = Payment::create(['customer_id' => $customer->id, 'original_amount' => 100, 'discount' => 0, 'final_amount' => 100, 'paid_amount' => 0, 'status' => 'unpaid']);
        $unpaid = Payment::create(['customer_id' => $customer->id, 'original_amount' => 80, 'discount' => 0, 'final_amount' => 80, 'paid_amount' => 0, 'status' => 'unpaid']);
        $overpaid->transactions()->create(['amount' => 125, 'status' => 'confirmed']);
        $unpaid->transactions()->create(['amount' => 20, 'status' => 'pending']);

        $kpis = app(AdminDashboardAnalytics::class)->data()['kpis'];

        $this->assertSame(180.0, $kpis['revenue']);
        $this->assertSame(125.0, $kpis['collected']);
        $this->assertSame(80.0, $kpis['outstanding']);
    }

    public function test_rsvp_rate_counts_submitted_active_parties_including_declines(): void
    {
        $event = $this->event('RSVP Event');
        $attending = InvitationParty::create(['event_id' => $event->id, 'name' => 'Attending', 'maximum_party_size' => 1]);
        $declined = InvitationParty::create(['event_id' => $event->id, 'name' => 'Declined', 'maximum_party_size' => 1]);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Pending', 'maximum_party_size' => 1]);
        $inactive = InvitationParty::create(['event_id' => $event->id, 'name' => 'Inactive', 'maximum_party_size' => 1, 'is_active' => false]);
        $attending->rsvp->update(['status' => 'attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $declined->rsvp->update(['status' => 'not_attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $inactive->rsvp->update(['status' => 'attending', 'submitted_at' => now(), 'last_updated_at' => now()]);

        $analytics = app(AdminDashboardAnalytics::class)->data();

        $this->assertSame(67, $analytics['kpis']['rsvp_rate']);
        $this->assertSame([
            ['label' => 'Attending', 'count' => 1, 'percentage' => 33],
            ['label' => 'Declined', 'count' => 1, 'percentage' => 33],
            ['label' => 'Awaiting Response', 'count' => 1, 'percentage' => 33],
        ], $analytics['rsvp_breakdown']['items']);
    }

    public function test_revenue_trend_uses_financial_record_and_confirmed_transaction_dates_with_zero_months(): void
    {
        $customer = Customer::create(['name' => 'Trend Customer']);
        $revenueMonth = now()->startOfMonth()->subMonths(10)->addDays(4);
        $collectionMonth = now()->startOfMonth()->subMonths(4)->addDays(12);
        $payment = Payment::create(['customer_id' => $customer->id, 'original_amount' => 100, 'discount' => 10, 'final_amount' => 90, 'paid_amount' => 0, 'status' => 'unpaid']);
        $payment->forceFill(['created_at' => $revenueMonth, 'updated_at' => $revenueMonth])->saveQuietly();
        $confirmed = $payment->transactions()->create(['amount' => 40, 'payment_date' => $collectionMonth->toDateString(), 'status' => 'confirmed']);
        $confirmed->forceFill(['created_at' => now()->startOfMonth()->subMonth(), 'updated_at' => now()->startOfMonth()->subMonth()])->saveQuietly();
        $payment->transactions()->create(['amount' => 50, 'payment_date' => $collectionMonth->toDateString(), 'status' => 'pending']);

        $trend = app(AdminDashboardAnalytics::class)->data()['revenue_trend'];
        $revenueIndex = array_search($revenueMonth->format('M'), $trend['labels'], true);
        $collectionIndex = array_search($collectionMonth->format('M'), $trend['labels'], true);

        $this->assertNotFalse($revenueIndex);
        $this->assertNotFalse($collectionIndex);
        $this->assertSame(90.0, $trend['revenue'][$revenueIndex]);
        $this->assertSame(40.0, $trend['collected'][$collectionIndex]);
        $this->assertSame(0.0, $trend['revenue'][0]);
    }

    public function test_event_status_and_event_type_analytics_use_business_facing_categories(): void
    {
        $live = $this->event('Live Wedding', null, null, null, 'published');
        EventPublication::create(['event_id' => $live->id, 'version' => 1, 'snapshot' => [], 'snapshot_hash' => str_repeat('a', 64), 'published_at' => now()]);
        $setup = $this->event('Birthday Setup');
        $setup->update(['event_type' => 'birthday']);
        $this->event('Archived Wedding', null, null, null, 'archived');

        $analytics = app(AdminDashboardAnalytics::class)->data();

        $this->assertSame([
            ['label' => 'Live', 'count' => 1, 'percentage' => 33],
            ['label' => 'Setup', 'count' => 1, 'percentage' => 33],
            ['label' => 'Archived', 'count' => 1, 'percentage' => 33],
        ], $analytics['event_status']);
        $this->assertSame([
            ['label' => 'Wedding', 'count' => 2],
            ['label' => 'Birthday', 'count' => 1],
        ], $analytics['events_by_type']);
    }

    public function test_upcoming_events_are_date_and_id_ordered_and_use_exact_capacity(): void
    {
        $package = EventPackage::create(['name' => 'Package', 'minimum_guests' => 1, 'maximum_guests' => 100, 'price' => 100, 'is_active' => true]);
        $later = $this->event('Later', today()->addDays(3), $package, 75);
        $first = $this->event('First', today()->addDay(), $package, 60);
        $sameDate = $this->event('Same date', today()->addDay(), $package);
        $archived = $this->event('Archived', today()->addDay(), $package, 20, 'archived');

        $upcoming = collect(app(AdminDashboardAnalytics::class)->data()['upcoming']);

        $this->assertSame([$first->id, $sameDate->id, $later->id], $upcoming->pluck('id')->all());
        $this->assertSame(60, $upcoming->firstWhere('id', $first->id)['capacity']);
        $this->assertSame(100, $upcoming->firstWhere('id', $sameDate->id)['capacity']);
        $this->assertFalse($upcoming->contains('id', $archived->id));
    }

    public function test_only_admins_receive_global_dashboard_analytics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $support = User::factory()->create(['role' => 'support']);

        $this->actingAs($admin)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->has('analytics.kpis'));
        $this->actingAs($customer)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->missing('analytics'));
        $this->actingAs($support)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->missing('analytics'));
    }

    private function event(string $title, mixed $date = null, ?EventPackage $package = null, ?int $capacity = null, string $status = 'draft'): Event
    {
        $customer = Customer::create(['name' => "{$title} Customer"]);

        return Event::create([
            'customer_id' => $customer->id,
            'event_package_id' => $package?->id,
            'guest_capacity' => $capacity,
            'title' => $title,
            'event_type' => 'wedding',
            'host_name' => 'Host',
            'main_date' => $date,
            'status' => $status,
        ]);
    }
}
