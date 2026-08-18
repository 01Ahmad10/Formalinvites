<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\Template;
use App\Models\User;
use App\Support\InvitationTemplateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventTimingTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_day_and_explicit_overnight_event_timing_are_valid(): void
    {
        [$event, $owner] = $this->event();

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location(['end_time' => '23:00']))->assertRedirect();
        $this->assertSame('2026-08-20', $event->fresh()->end_date->format('Y-m-d'));

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location(['end_time' => '03:00']))->assertRedirect();
        $this->assertSame('2026-08-21', $event->fresh()->end_date->format('Y-m-d'));
    }

    public function test_equal_end_time_is_rejected_and_empty_end_time_clears_the_derived_end_date(): void
    {
        [$event, $owner] = $this->event();
        $event->update(['end_date' => '2026-08-21', 'end_time' => '03:00']);
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location(['end_time' => '20:00']))->assertSessionHasErrors('end_time');
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location())->assertRedirect();

        $this->assertNull($event->fresh()->end_date);
        $this->assertNull($event->fresh()->end_time);
    }

    public function test_active_event_end_date_is_snapshotted_only_after_valid_save(): void
    {
        [$event, $owner] = $this->event();
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location(['end_time' => '03:00']))->assertRedirect();
        $this->assertDatabaseCount('event_publications', 2);
        $this->assertSame('2026-08-21', $event->fresh()->latestPublication()->snapshot['event']['end_date']);

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location(['end_time' => '20:00']))->assertSessionHasErrors('end_time');
        $this->assertDatabaseCount('event_publications', 2);
        $this->assertSame('2026-08-21', $event->fresh()->latestPublication()->snapshot['event']['end_date']);
    }

    public function test_customer_event_page_receives_human_timing_without_internal_member_or_system_data(): void
    {
        [$event, $owner] = $this->event();
        $event->update(['end_date' => '2026-08-21', 'end_time' => '03:00']);

        $this->actingAs($owner)->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('eventTiming.main_date', 'August 20, 2026')
            ->where('eventTiming.start_time', '8:00 PM')
            ->where('eventTiming.end_date', 'August 21, 2026')
            ->where('eventTiming.end_time', '3:00 AM')
            ->missing('event.members')
            ->missing('event.created_at')
            ->missing('event.updated_at'));
    }

    public function test_crafted_end_date_cannot_override_the_server_derived_date(): void
    {
        [$event, $owner] = $this->event();

        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'location']), $this->location(['end_date' => '2030-01-01', 'end_time' => '03:00']))->assertRedirect();

        $this->assertSame('2026-08-21', $event->fresh()->end_date->format('Y-m-d'));
    }

    private function event(): array
    {
        $customer = Customer::create(['name' => 'Customer']);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $package = EventPackage::create(['name' => 'Package', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 100, 'is_active' => true]);
        $template = Template::create(['name' => 'Romantic', 'slug' => 'romantic', 'component_key' => 'romantic-floral', 'default_settings' => InvitationTemplateSettings::defaults(), 'is_active' => true, 'is_customer_selectable' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'template_id' => $template->id, 'title' => 'Wedding', 'event_type' => 'wedding', 'host_name' => 'Maya', 'main_date' => '2026-08-20', 'start_time' => '20:00', 'event_timezone' => 'Asia/Beirut', 'status' => 'draft']);
        $event->members()->attach($owner, ['role' => 'owner']);

        return [$event, $owner];
    }

    private function location(array $overrides = []): array
    {
        return [...['main_date' => '2026-08-20', 'start_time' => '20:00', 'end_time' => null, 'event_timezone' => 'Asia/Beirut', 'venue' => 'Hall', 'address' => null, 'location_url' => null, 'dress_code' => null, 'parking_information' => null, 'transportation_information' => null, 'accommodation_information' => null, 'guest_information' => null], ...$overrides];
    }
}
