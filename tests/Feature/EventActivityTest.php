<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventActivity;
use App\Models\EventPackage;
use App\Models\InvitationParty;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_event_timezone_guest_information_and_an_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();

        $this->actingAs($admin)->put(route('events.update', $event), [
            'customer_id' => $event->customer_id,
            'event_package_id' => $event->event_package_id,
            'title' => $event->title,
            'event_type' => $event->event_type,
            'host_name' => $event->host_name,
            'event_timezone' => 'America/New_York',
            'dress_code' => 'Formal attire',
            'parking_information' => 'Use the north parking lot.',
            'transportation_information' => 'Shuttle available.',
            'accommodation_information' => 'Hotel rate available.',
            'guest_information' => 'Please arrive early.',
            'status' => 'draft',
        ])->assertRedirect(route('events.show', $event));

        $this->actingAs($admin)->post(route('events.activities.store', $event), $this->activityPayload())->assertRedirect(route('events.activities.index', $event));

        $activity = EventActivity::firstOrFail();
        $this->assertSame('America/New_York', $event->fresh()->event_timezone);
        $this->assertSame('Formal attire', $event->fresh()->dress_code);
        $this->assertSame('2026-10-15 18:00', $activity->starts_at->setTimezone('America/New_York')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-15 21:00', $activity->ends_at->setTimezone('America/New_York')->format('Y-m-d H:i'));
    }

    public function test_event_timezone_defaults_safely_and_invalid_timezone_and_maps_url_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $event = Event::create(['customer_id' => $customer->id, 'title' => 'Existing event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);

        $this->assertSame(config('app.timezone'), $event->fresh()->event_timezone);

        $this->actingAs($admin)->put(route('events.update', $event), [
            'customer_id' => $event->customer_id,
            'title' => $event->title,
            'event_type' => $event->event_type,
            'host_name' => $event->host_name,
            'event_timezone' => 'EST',
            'status' => 'draft',
        ])->assertSessionHasErrors('event_timezone');

        $this->actingAs($admin)->post(route('events.activities.store', $event), $this->activityPayload(['location_url' => 'not a valid url']))->assertSessionHasErrors('location_url');
    }

    public function test_edit_event_shares_the_full_timezone_list_and_the_saved_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();
        $event->update(['event_timezone' => 'America/New_York']);
        $newYorkIndex = array_search('America/New_York', DateTimeZone::listIdentifiers(), true);

        $this->actingAs($admin)->get(route('events.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Form')
            ->where('event.event_timezone', 'America/New_York')
            ->where("timezones.{$newYorkIndex}", 'America/New_York')
        );
    }

    public function test_activity_end_must_be_after_start_but_can_cross_midnight(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();
        $payload = $this->activityPayload(['end_date' => '2026-10-15', 'end_time' => '17:00']);

        $this->actingAs($admin)->post(route('events.activities.store', $event), $payload)->assertSessionHasErrors('end_time');

        $this->actingAs($admin)->post(route('events.activities.store', $event), $this->activityPayload(['end_date' => '2026-10-16', 'end_time' => '01:00']))->assertRedirect();
        $this->assertDatabaseCount('event_activities', 1);
    }

    public function test_beirut_activity_wall_clock_time_round_trips_through_storage_schedule_and_edit_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();
        $event->update(['event_timezone' => 'Asia/Beirut']);

        $this->actingAs($admin)->post(route('events.activities.store', $event), $this->activityPayload([
            'start_date' => '2026-08-12',
            'start_time' => '16:00',
            'end_date' => '2026-08-12',
            'end_time' => '17:00',
        ]))->assertRedirect();

        $activity = EventActivity::firstOrFail();
        $this->assertSame('2026-08-12 13:00:00', $activity->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-12 16:00', $activity->starts_at->setTimezone('Asia/Beirut')->format('Y-m-d H:i'));

        $this->actingAs($admin)->get(route('events.activities.index', $event))->assertInertia(fn (Assert $page) => $page
            ->where('activities.0.start_time', '4:00 PM')
            ->where('activities.0.end_time', '5:00 PM')
        );
        $this->actingAs($admin)->get(route('events.activities.edit', [$event, $activity]))->assertInertia(fn (Assert $page) => $page
            ->where('activity.start_date', '2026-08-12')
            ->where('activity.start_time', '16:00')
            ->where('activity.end_date', '2026-08-12')
            ->where('activity.end_time', '17:00')
        );
    }

    public function test_new_york_and_overnight_activity_wall_clock_times_round_trip_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event();
        $event->update(['event_timezone' => 'America/New_York']);

        $this->actingAs($admin)->post(route('events.activities.store', $event), $this->activityPayload([
            'title' => 'New York reception',
            'start_date' => '2026-08-12',
            'start_time' => '16:00',
            'end_date' => '2026-08-12',
            'end_time' => '17:00',
            'display_order' => 1,
        ]))->assertRedirect();
        $this->actingAs($admin)->post(route('events.activities.store', $event), $this->activityPayload([
            'title' => 'After party',
            'start_date' => '2026-08-12',
            'start_time' => '23:00',
            'end_date' => '2026-08-13',
            'end_time' => '02:00',
            'display_order' => 2,
        ]))->assertRedirect();

        $reception = EventActivity::where('title', 'New York reception')->firstOrFail();
        $afterParty = EventActivity::where('title', 'After party')->firstOrFail();
        $this->assertSame('2026-08-12 20:00', $reception->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-08-12 23:00', $afterParty->starts_at->setTimezone('America/New_York')->format('Y-m-d H:i'));
        $this->assertSame('2026-08-13 02:00', $afterParty->ends_at->setTimezone('America/New_York')->format('Y-m-d H:i'));

        $this->actingAs($admin)->get(route('events.activities.index', $event))->assertInertia(fn (Assert $page) => $page
            ->where('activities.0.start_time', '4:00 PM')
            ->where('activities.1.start_time', '11:00 PM')
            ->where('activities.1.end_date', 'August 13, 2026')
            ->where('activities.1.end_time', '2:00 AM')
        );
        $this->actingAs($admin)->get(route('events.activities.edit', [$event, $reception]))->assertInertia(fn (Assert $page) => $page
            ->where('activity.start_time', '16:00')
            ->where('activity.end_time', '17:00')
        );
    }

    public function test_activities_are_ordered_and_access_is_limited_to_authorized_event_users(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $editor = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $support = User::factory()->create(['role' => 'support']);
        $outsider = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);
        $event = $this->event($customer);
        $event->members()->attach($editor, ['role' => 'editor']);
        $first = $event->activities()->create(['title' => 'First', 'starts_at' => CarbonImmutable::parse('2026-10-15 14:00', 'UTC'), 'display_order' => 1]);
        $event->activities()->create(['title' => 'Second', 'starts_at' => CarbonImmutable::parse('2026-10-15 12:00', 'UTC'), 'display_order' => 2]);
        $other = $this->event();
        $otherActivity = $other->activities()->create(['title' => 'Private', 'starts_at' => CarbonImmutable::parse('2026-10-15 12:00', 'UTC')]);

        $this->actingAs($editor)->get(route('events.activities.index', $event))
            ->assertInertia(fn (Assert $page) => $page->component('Events/Activities/Index')->where('activities.0.title', 'First')->where('activities.1.title', 'Second')->where('canManage', true));
        $this->actingAs($editor)->put(route('events.activities.update', [$event, $first]), $this->activityPayload(['title' => 'Updated']))->assertRedirect();
        $this->actingAs($editor)->patch(route('events.activities.active', [$event, $first]), ['is_active' => false])->assertRedirect();
        $this->assertDatabaseHas('event_activities', ['id' => $first->id, 'is_active' => false]);
        $this->actingAs($support)->get(route('events.activities.index', $event))->assertOk();
        $this->actingAs($support)->post(route('events.activities.store', $event), $this->activityPayload())->assertForbidden();
        $this->actingAs($outsider)->get(route('events.activities.index', $event))->assertForbidden();
        $this->actingAs($editor)->get(route('events.activities.show', [$event, $otherActivity]))->assertNotFound();
    }

    public function test_public_rsvp_shares_only_active_schedule_and_guest_logistics(): void
    {
        $event = $this->event();
        $event->update(['event_timezone' => 'America/New_York', 'dress_code' => 'Black tie', 'parking_information' => 'Valet parking.', 'guest_information' => 'Bring your invitation.']);
        $event->activities()->create(['title' => 'Ceremony', 'activity_type' => 'ceremony', 'description' => 'Please be seated.', 'starts_at' => CarbonImmutable::parse('2026-10-15 18:00', 'America/New_York')->utc(), 'ends_at' => CarbonImmutable::parse('2026-10-15 19:00', 'America/New_York')->utc(), 'venue' => 'Garden', 'location_url' => 'https://maps.example.test/garden', 'display_order' => 1, 'is_active' => true]);
        $event->activities()->create(['title' => 'Private draft', 'starts_at' => CarbonImmutable::parse('2026-10-15 20:00', 'America/New_York')->utc(), 'is_active' => false]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 2]);

        $this->get(route('public.rsvp.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicRsvp')
            ->where('party.event.schedule.0.title', 'Ceremony')
            ->where('party.event.schedule.0.start_time', '6:00 PM')
            ->where('party.event.guest_information.dress_code', 'Black tie')
            ->where('party.event.guest_information.parking_information', 'Valet parking.')
            ->missing('party.event.schedule.0.id')
            ->missing('party.event.schedule.0.is_active')
            ->missing('party.event.schedule.1')
            ->missing('party.event.customer_id')
        );
    }

    private function event(?Customer $customer = null): Event
    {
        $customer ??= Customer::create(['name' => 'Customer '.uniqid()]);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);

        return Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Event '.uniqid(), 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft', 'event_timezone' => 'UTC', 'rsvp_deadline' => '2026-10-14']);
    }

    private function activityPayload(array $overrides = []): array
    {
        return [...[
            'title' => 'Dinner',
            'activity_type' => 'reception',
            'description' => 'Dinner service.',
            'start_date' => '2026-10-15',
            'start_time' => '18:00',
            'end_date' => '2026-10-15',
            'end_time' => '21:00',
            'venue' => 'Ballroom',
            'address' => '1 Main Street',
            'location_url' => 'https://maps.example.test/ballroom',
            'location_notes' => 'Use the east entrance.',
            'display_order' => 1,
        ], ...$overrides];
    }
}
