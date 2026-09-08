<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use App\Support\InvitationPublicationSnapshotBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RsvpDeadlineTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_only_deadline_is_open_until_the_exact_end_of_the_application_day(): void
    {
        config(['app.timezone' => 'UTC']);
        $event = $this->event('2026-08-12');

        $deadline = CarbonImmutable::parse('2026-08-12 23:59:59.999999', 'UTC');

        $this->assertFalse($event->isRsvpClosed($deadline->subMicrosecond()));
        $this->assertTrue($event->isRsvpClosed($deadline));
        $this->assertTrue($event->isRsvpClosed($deadline->addMicrosecond()));
    }

    public function test_public_submission_is_allowed_before_and_rejected_after_a_date_only_deadline(): void
    {
        config(['app.timezone' => 'UTC']);
        $event = $this->event('2026-08-12');
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Party', 'maximum_party_size' => 1]);
        $this->publish($event);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 23:59:59', 'UTC'));
        $this->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending'])->assertRedirect();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 00:00:00', 'UTC'));
        $this->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'attending'])->assertSessionHasErrors('status');

        CarbonImmutable::setTestNow();
    }

    public function test_deadline_uses_the_configured_application_timezone_without_utc_date_shift(): void
    {
        config(['app.timezone' => 'Asia/Beirut']);
        $event = $this->event('2026-08-12');

        $deadline = $event->rsvpDeadlineAt();

        $this->assertSame('Asia/Beirut', $deadline->getTimezone()->getName());
        $this->assertSame('2026-08-12 23:59:59', $deadline->format('Y-m-d H:i:s'));
        $this->assertFalse($event->isRsvpClosed(CarbonImmutable::parse('2026-08-12 20:59:59.999998', 'UTC')));
        $this->assertTrue($event->isRsvpClosed(CarbonImmutable::parse('2026-08-12 21:00:00', 'UTC')));
    }

    public function test_date_only_deadline_uses_each_events_timezone_instead_of_the_application_timezone(): void
    {
        config(['app.timezone' => 'UTC']);
        $event = $this->event('2026-08-12');
        $event->update(['event_timezone' => 'America/New_York']);

        $deadline = $event->rsvpDeadlineAt();

        $this->assertSame('America/New_York', $deadline->getTimezone()->getName());
        $this->assertSame('2026-08-12 23:59:59', $deadline->format('Y-m-d H:i:s'));
        $this->assertFalse($event->isRsvpClosed(CarbonImmutable::parse('2026-08-13 03:59:59.999998', 'UTC')));
        $this->assertTrue($event->isRsvpClosed(CarbonImmutable::parse('2026-08-13 04:00:00', 'UTC')));
    }

    public function test_los_angeles_event_deadline_remains_open_until_its_local_end_of_day(): void
    {
        config(['app.timezone' => 'UTC']);
        $event = $this->event('2026-08-12');
        $event->update(['event_timezone' => 'America/Los_Angeles']);

        $this->assertFalse($event->isRsvpClosed(CarbonImmutable::parse('2026-08-13 06:59:59.999998', 'UTC')));
        $this->assertTrue($event->isRsvpClosed(CarbonImmutable::parse('2026-08-13 07:00:00', 'UTC')));
    }

    private function event(string $deadline): Event
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);

        return Event::create([
            'customer_id' => $customer->id,
            'event_package_id' => $package->id,
            'title' => 'Event '.uniqid(),
            'event_type' => 'wedding',
            'host_name' => 'Host',
            'status' => 'draft',
            'rsvp_deadline' => $deadline,
        ]);
    }

    private function publish(Event $event): EventPublication
    {
        $event->update(['status' => 'published']);
        $builder = app(InvitationPublicationSnapshotBuilder::class);
        $snapshot = $builder->build($event->fresh());

        return EventPublication::create([
            'event_id' => $event->id,
            'version' => $event->publications()->count() + 1,
            'snapshot' => $snapshot,
            'snapshot_hash' => $builder->hashSnapshot($snapshot),
            'published_at' => now(),
        ]);
    }
}
