<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationParty;
use App\Models\InvitationEntitlement;
use App\Models\Template;
use App\Models\User;
use App\Support\InvitationTemplateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerAllowanceAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_allowance_counts_claimed_entitlements_including_archived_events(): void
    {
        [$customer, $owner, $admin, $package] = $this->customerWithUsers(2);
        $first = $this->event($customer, $package, 'First');
        $second = $this->event($customer, $package, 'Second', 'archived');
        $first->members()->attach($owner, ['role' => 'owner']);
        $second->members()->attach($owner, ['role' => 'owner']);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 10, 'status' => InvitationEntitlement::CLAIMED, 'claimed_event_id' => $first->id, 'claimed_at' => now()]);
        InvitationEntitlement::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'exact_guest_capacity' => 10, 'status' => InvitationEntitlement::CLAIMED, 'claimed_event_id' => $second->id, 'claimed_at' => now()]);

        $this->actingAs($owner)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('allowance.allowed_events', 2)
            ->where('allowance.used_events', 2)
            ->where('allowance.remaining_events', 0)
            ->where('allowance.can_create_event', false)
        );

        $this->actingAs($owner)->post(route('events.store'), ['start_setup' => true])->assertSessionHasErrors('entitlement_id');
        $this->actingAs($admin)->post(route('events.store'), ['customer_id' => $customer->id])->assertSessionHasErrors('entitlement_id');
    }

    public function test_admin_provisioning_requires_at_least_one_entitlement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = EventPackage::create(['name' => 'Small', 'minimum_guests' => 1, 'maximum_guests' => 20, 'price' => 20, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.clients.store'), [
            'name' => 'New Customer', 'phone' => null, 'entitlements' => [],
            'primary_name' => 'Primary User', 'primary_email' => 'primary@example.test', 'primary_password' => 'password123',
        ])->assertSessionHasErrors('entitlements');

        $this->actingAs($admin)->post(route('admin.clients.store'), [
            'name' => 'New Customer', 'phone' => null, 'entitlements' => [['event_package_id' => $package->id, 'exact_guest_capacity' => 10], ['event_package_id' => $package->id, 'exact_guest_capacity' => 10]],
            'primary_name' => 'Primary User', 'primary_email' => 'primary@example.test', 'primary_password' => 'password123',
        ])->assertRedirect(route('admin.customers.index'));

        $customer = Customer::where('name', 'New Customer')->firstOrFail();
        $this->assertSame(2, $customer->allowed_events);
        $this->assertSame(0, $customer->usedEvents());
        $this->assertSame(2, $customer->remainingEvents());
        $this->assertSame($package->id, $customer->invitationEntitlements()->firstOrFail()->event_package_id);
    }

    public function test_complete_disable_enable_archive_and_public_routes_follow_the_operational_lifecycle(): void
    {
        [$customer, $owner, $admin, $package] = $this->customerWithUsers(2);
        $event = $this->event($customer, $package, 'Lifecycle Event');
        $event->members()->attach($owner, ['role' => 'owner']);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 1]);

        $this->get(route('public.invitation.show', $party->rsvp_token))->assertNotFound();
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertSame('published', $event->fresh()->status);
        $this->assertSame(1, $event->publications()->count());
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertOk();
        $this->get(route('public.rsvp.show', $party->rsvp_token))->assertOk();

        $this->actingAs($admin)->patch(route('events.publication.disable', $event))->assertRedirect();
        $this->assertSame('disabled', $event->fresh()->status);
        $this->actingAs($owner)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('invitations.0.status', 'disabled')
        );
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertNotFound();
        $this->get(route('public.rsvp.show', $party->rsvp_token))->assertNotFound();
        $this->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending'])->assertNotFound();

        $this->actingAs($admin)->patch(route('events.publication.enable', $event))->assertRedirect();
        $this->assertSame('published', $event->fresh()->status);
        $this->assertSame(1, $event->publications()->count());
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertOk();

        $this->actingAs($admin)->patch(route('events.publication.archive', $event))->assertRedirect();
        $this->assertSame('archived', $event->fresh()->status);
        $this->assertSame(1, $event->publications()->count());
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertNotFound();
        $this->get(route('public.rsvp.show', $party->rsvp_token))->assertNotFound();
    }

    public function test_admin_uses_the_same_builder_and_only_changed_live_content_creates_a_new_publication(): void
    {
        [$customer, $owner, $admin, $package] = $this->customerWithUsers(2);
        $event = $this->event($customer, $package, 'Builder Event');
        $event->members()->attach($owner, ['role' => 'owner']);
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();

        $this->actingAs($admin)->get(route('events.builder', $event))->assertOk();
        $this->actingAs($admin)->put(route('events.builder.update', $event), $this->builderPayload($event, ['title' => 'Admin updated event']))->assertSessionHasNoErrors();
        $this->assertSame(2, $event->publications()->count());
        $this->assertSame($customer->id, $event->fresh()->customer_id);

        $this->actingAs($admin)->put(route('events.builder.update', $event), $this->builderPayload($event->fresh(), ['title' => 'Admin updated event']))->assertSessionHasNoErrors();
        $this->assertSame(2, $event->publications()->count());
    }

    public function test_legacy_approval_values_are_safe_but_never_presented_as_customer_states(): void
    {
        [$customer, $owner, , $package] = $this->customerWithUsers(2);
        $event = $this->event($customer, $package, 'Historical', 'approved');
        $event->members()->attach($owner, ['role' => 'owner']);

        $this->assertSame('setup', $event->invitationStatus());
        $this->actingAs($owner)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('invitations.0.status', 'setup')
        );
    }

    private function customerWithUsers(int $allowance): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid(), 'allowed_events' => $allowance]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 50, 'is_active' => true]);

        return [$customer, $owner, $admin, $package];
    }

    private function event(Customer $customer, EventPackage $package, string $title, string $status = 'draft'): Event
    {
        $template = Template::create(['name' => 'Romantic '.uniqid(), 'slug' => 'romantic-'.uniqid(), 'component_key' => 'romantic-floral', 'default_settings' => InvitationTemplateSettings::defaults(), 'is_active' => true, 'is_customer_selectable' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'guest_capacity' => 10, 'template_id' => $template->id, 'title' => $title, 'event_type' => 'wedding', 'host_name' => 'Host', 'main_date' => '2027-10-12', 'start_time' => '16:00', 'event_timezone' => 'America/New_York', 'status' => $status]);
        $event->templateSetting()->create(['settings' => []]);

        return $event;
    }

    private function eventPayload(array $overrides = []): array
    {
        return [...['title' => 'Created Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'main_date' => '2027-10-12', 'start_time' => '16:00', 'event_timezone' => 'America/New_York', 'status' => 'draft'], ...$overrides];
    }

    private function builderPayload(Event $event, array $overrides = []): array
    {
        return [...[
            'event_type' => $event->event_type, 'title' => $event->title, 'host_name' => $event->host_name,
            'second_host_name' => $event->second_host_name, 'description' => $event->description,
            'main_date' => $event->main_date?->format('Y-m-d'), 'start_time' => $event->start_time, 'end_time' => $event->end_time,
            'event_timezone' => $event->event_timezone, 'venue' => $event->venue, 'address' => $event->address,
            'location_url' => $event->location_url, 'dress_code' => $event->dress_code,
            'parking_information' => $event->parking_information, 'transportation_information' => $event->transportation_information,
            'accommodation_information' => $event->accommodation_information, 'guest_information' => $event->guest_information,
            'rsvp_deadline' => $event->rsvp_deadline?->format('Y-m-d'), 'template_id' => $event->template_id, 'settings' => $event->templateSetting?->settings ?? [],
            'content' => ['primary_locale' => 'en', 'story_enabled' => false, 'story_heading' => null, 'story_body' => null, 'gift_registry_enabled' => false, 'gift_registry_intro' => null, 'ending_enabled' => false, 'ending_title' => null, 'ending_message' => null],
            'gift_methods' => [],
        ], ...$overrides];
    }
}
