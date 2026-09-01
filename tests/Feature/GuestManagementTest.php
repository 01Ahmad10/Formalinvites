<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventMealOption;
use App\Models\EventPackage;
use App\Models\InvitationParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_has_multiple_invitation_parties_and_parties_have_multiple_members(): void
    {
        $event = $this->eventWithPackage(10);
        $family = InvitationParty::create(['event_id' => $event->id, 'name' => 'Smith Family', 'maximum_party_size' => 4]);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Nadia Saad', 'maximum_party_size' => 1]);
        $family->members()->createMany([['first_name' => 'John', 'last_name' => 'Smith', 'member_type' => 'adult'], ['first_name' => 'Emma', 'last_name' => 'Smith', 'member_type' => 'child']]);

        $this->assertCount(2, $event->invitationParties);
        $this->assertSame($event->id, $family->event->id);
        $this->assertCount(2, $family->members);
    }

    public function test_capacity_uses_active_maximum_party_size_and_cannot_be_exceeded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventWithPackage(10);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Couple', 'maximum_party_size' => 4]);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 4]);

        $this->assertSame(8, $event->allocatedGuestCapacity());
        $this->actingAs($admin)->post(route('events.guests.store', $event), ['name' => 'Too Large', 'maximum_party_size' => 3])->assertSessionHasErrors('maximum_party_size');
        $this->assertDatabaseMissing('invitation_parties', ['event_id' => $event->id, 'name' => 'Too Large']);
    }

    public function test_exact_event_capacity_limits_parties_even_when_pricing_package_has_more_capacity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Exact Capacity']);
        $package = EventPackage::create(['name' => '201–250', 'minimum_guests' => 201, 'maximum_guests' => 250, 'price' => 130, 'is_active' => true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$package->id,'guest_capacity'=>220,'title'=>'Capacity Event','event_type'=>'wedding','host_name'=>'Host']);
        InvitationParty::create(['event_id'=>$event->id,'name'=>'Allocated','maximum_party_size'=>220]);

        $this->assertSame(220, $event->effectiveGuestCapacity());
        $this->actingAs($admin)->get(route('events.guests.index', $event))->assertInertia(fn (Assert $page) => $page->where('summary.guest_capacity', 220)->where('summary.remaining_capacity', 0));
        $this->actingAs($admin)->post(route('events.guests.store', $event), ['name'=>'Too many','maximum_party_size'=>1])->assertSessionHasErrors('maximum_party_size');
    }

    public function test_editing_a_party_excludes_its_current_capacity_but_still_prevents_overflow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventWithPackage(10);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Couple', 'maximum_party_size' => 2]);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 6]);

        $this->actingAs($admin)->put(route('events.guests.update', [$event, $party]), $this->partyPayload('Couple', 5))->assertSessionHasErrors('maximum_party_size');
        $party->refresh();
        $this->assertSame(2, $party->maximum_party_size);
        $this->actingAs($admin)->put(route('events.guests.update', [$event, $party]), $this->partyPayload('Couple', 4))->assertRedirect();
        $this->assertDatabaseHas('invitation_parties', ['id' => $party->id, 'maximum_party_size' => 4]);
    }

    public function test_party_members_cannot_exceed_maximum_size_but_party_size_can_exceed_known_members(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventWithPackage(10);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Couple', 'maximum_party_size' => 2]);

        $this->actingAs($admin)->post(route('events.guests.members.store', [$event, $party]), $this->memberPayload('John'))->assertRedirect();
        $this->actingAs($admin)->post(route('events.guests.members.store', [$event, $party]), $this->memberPayload('Sarah'))->assertRedirect();
        $this->actingAs($admin)->post(route('events.guests.members.store', [$event, $party]), $this->memberPayload('Emma'))->assertSessionHasErrors('first_name');
        $this->assertCount(2, $party->fresh()->members);
        $this->actingAs($admin)->put(route('events.guests.update', [$event, $party]), $this->partyPayload('Couple', 1))->assertSessionHasErrors('maximum_party_size');

        $family = InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 4]);
        $this->actingAs($admin)->post(route('events.guests.members.store', [$event, $family]), $this->memberPayload('David', 'child'))->assertRedirect();
        $this->assertCount(1, $family->fresh()->members);
    }

    public function test_admin_and_authorized_event_member_can_manage_guest_lists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = $this->eventWithPackage(10, $customer);
        $event->members()->attach($owner, ['role' => 'editor']);

        $this->actingAs($admin)->get(route('events.guests.index', $event))->assertOk();
        $this->actingAs($owner)->post(route('events.guests.store', $event), $this->partyPayload('Owner Party', 2))->assertRedirect();
        $party = InvitationParty::where('name', 'Owner Party')->firstOrFail();
        $this->actingAs($owner)->post(route('events.guests.members.store', [$event, $party]), $this->memberPayload('Owner Member'))->assertRedirect();
    }

    public function test_unauthorized_customer_cannot_view_or_modify_another_events_guest_list_or_party(): void
    {
        $first = Customer::create(['name' => 'First']);
        $second = Customer::create(['name' => 'Second']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $first->id]);
        $other = User::factory()->create(['role' => 'customer', 'customer_id' => $second->id]);
        $ownEvent = $this->eventWithPackage(10, $first);
        $foreignEvent = $this->eventWithPackage(10, $second);
        $ownEvent->members()->attach($user, ['role' => 'owner']);
        $foreignEvent->members()->attach($other, ['role' => 'owner']);
        $foreignParty = InvitationParty::create(['event_id' => $foreignEvent->id, 'name' => 'Private Party', 'maximum_party_size' => 2]);

        $this->actingAs($user)->get(route('events.guests.index', $foreignEvent))->assertForbidden();
        $this->actingAs($user)->put(route('events.guests.update', [$ownEvent, $foreignParty]), $this->partyPayload('Attempt', 2))->assertNotFound();
        $this->actingAs($user)->put(route('events.guests.update', [$foreignEvent, $foreignParty]), $this->partyPayload('Attempt', 2))->assertForbidden();
    }

    public function test_unauthorized_customer_cannot_modify_another_events_party_member_by_crafted_url(): void
    {
        $first = Customer::create(['name' => 'First']);
        $second = Customer::create(['name' => 'Second']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $first->id]);
        $other = User::factory()->create(['role' => 'customer', 'customer_id' => $second->id]);
        $ownEvent = $this->eventWithPackage(10, $first);
        $foreignEvent = $this->eventWithPackage(10, $second);
        $ownEvent->members()->attach($user, ['role' => 'owner']);
        $foreignEvent->members()->attach($other, ['role' => 'owner']);
        $ownParty = InvitationParty::create(['event_id' => $ownEvent->id, 'name' => 'Own Party', 'maximum_party_size' => 2]);
        $foreignParty = InvitationParty::create(['event_id' => $foreignEvent->id, 'name' => 'Foreign Party', 'maximum_party_size' => 2]);
        $member = $foreignParty->members()->create($this->memberPayload('Private'));

        $this->actingAs($user)->put(route('events.guests.members.update', [$ownEvent, $ownParty, $member]), $this->memberPayload('Attempt'))->assertNotFound();
        $this->assertDatabaseHas('party_members', ['id' => $member->id, 'first_name' => 'Private']);
    }

    public function test_guest_search_is_scoped_to_the_authorized_event(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $ownEvent = $this->eventWithPackage(10, $customer);
        $otherEvent = $this->eventWithPackage(10, Customer::create(['name' => 'Other']));
        $ownEvent->members()->attach($user, ['role' => 'owner']);
        InvitationParty::create(['event_id' => $ownEvent->id, 'name' => 'Visible Search Party', 'maximum_party_size' => 1]);
        InvitationParty::create(['event_id' => $otherEvent->id, 'name' => 'Private Search Party', 'maximum_party_size' => 1]);

        $this->actingAs($user)->get(route('events.guests.index', [$ownEvent, 'search' => 'Search Party']))->assertSee('Visible Search Party')->assertDontSee('Private Search Party');
    }

    public function test_admin_cannot_assign_a_lower_package_than_existing_allocated_guest_capacity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $large = EventPackage::create(['name' => 'Large', 'minimum_guests' => 1, 'maximum_guests' => 10, 'price' => 25, 'is_active' => true]);
        $small = EventPackage::create(['name' => 'Small', 'minimum_guests' => 1, 'maximum_guests' => 5, 'price' => 20, 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $large->id, 'title' => 'Capacity Event', 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Family', 'maximum_party_size' => 6]);

        $this->actingAs($admin)->put(route('events.update', $event), ['customer_id' => $customer->id, 'event_package_id' => $small->id, 'title' => $event->title, 'event_type' => $event->event_type, 'host_name' => $event->host_name, 'status' => $event->status])->assertSessionHasErrors('event_package_id');
    }

    public function test_admin_cannot_assign_a_package_that_does_not_contain_exact_guest_capacity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Customer']);
        $current = EventPackage::create(['name'=>'201–250','minimum_guests'=>201,'maximum_guests'=>250,'price'=>130,'is_active'=>true]);
        $lower = EventPackage::create(['name'=>'151–200','minimum_guests'=>151,'maximum_guests'=>200,'price'=>110,'is_active'=>true]);
        $event = Event::create(['customer_id'=>$customer->id,'event_package_id'=>$current->id,'guest_capacity'=>220,'title'=>'Capacity Event','event_type'=>'wedding','host_name'=>'Host','status'=>'draft']);

        $this->actingAs($admin)->put(route('events.update', $event), ['customer_id'=>$customer->id,'event_package_id'=>$lower->id,'guest_capacity'=>220,'title'=>$event->title,'event_type'=>$event->event_type,'host_name'=>$event->host_name,'status'=>$event->status])->assertSessionHasErrors('guest_capacity');
    }

    public function test_admin_can_edit_an_invitation_party(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventWithPackage(10);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Old Party', 'maximum_party_size' => 2]);

        $this->actingAs($admin)->put(route('events.guests.update', [$event, $party]), ['name' => 'Updated Party', 'primary_contact_name' => 'Updated Contact', 'email' => 'updated@example.test', 'phone' => '555-0199', 'maximum_party_size' => 3, 'table_name' => 'B2', 'notes' => 'Updated notes'])->assertRedirect();
        $this->assertDatabaseHas('invitation_parties', ['id' => $party->id, 'name' => 'Updated Party', 'primary_contact_name' => 'Updated Contact', 'email' => 'updated@example.test', 'phone' => '555-0199', 'maximum_party_size' => 3, 'table_name' => 'B2', 'notes' => 'Updated notes']);
    }

    public function test_authorized_event_user_can_edit_their_invitation_party(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = $this->eventWithPackage(10, $customer);
        $event->members()->attach($user, ['role' => 'owner']);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Original Party', 'maximum_party_size' => 2]);

        $this->actingAs($user)->put(route('events.guests.update', [$event, $party]), $this->partyPayload('Customer Updated Party', 3))->assertRedirect();
        $this->assertDatabaseHas('invitation_parties', ['id' => $party->id, 'name' => 'Customer Updated Party', 'maximum_party_size' => 3]);
    }

    public function test_reactivation_cannot_exceed_event_capacity_and_another_customer_cannot_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);
        $event = $this->eventWithPackage(10);
        InvitationParty::create(['event_id' => $event->id, 'name' => 'Active Family', 'maximum_party_size' => 8]);
        $inactiveParty = InvitationParty::create(['event_id' => $event->id, 'name' => 'Inactive Family', 'maximum_party_size' => 4, 'is_active' => false]);

        $this->actingAs($admin)->patch(route('events.guests.active', [$event, $inactiveParty]), ['is_active' => true])->assertSessionHasErrors('maximum_party_size');
        $this->assertDatabaseHas('invitation_parties', ['id' => $inactiveParty->id, 'is_active' => false]);
        $this->actingAs($other)->put(route('events.guests.update', [$event, $inactiveParty]), $this->partyPayload('Other Customer Attempt', 4))->assertForbidden();
    }

    public function test_party_details_show_only_current_rsvp_additional_guests_separately_and_format_system_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->eventWithPackage(10);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Williams Family', 'maximum_party_size' => 4]);
        $party->members()->createMany([
            ['first_name' => 'Michael', 'last_name' => 'Williams', 'member_type' => 'adult'],
            ['first_name' => 'Anna', 'last_name' => 'Williams', 'member_type' => 'adult'],
        ]);
        DB::table('invitation_parties')->where('id', $party->id)->update(['created_at' => '2026-08-11 11:01:26', 'updated_at' => '2026-08-11 11:06:00']);
        $meal = EventMealOption::create(['event_id' => $event->id, 'name' => 'Chicken', 'is_active' => true]);
        $party->rsvp->update(['status' => 'attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $party->rsvp->personResponses()->create(['first_name' => 'Alex', 'last_name' => 'Williams', 'member_type' => 'child', 'is_original_party_member' => false, 'is_attending' => true, 'event_meal_option_id' => $meal->id, 'dietary_note' => 'Nut allergy']);

        $otherEvent = $this->eventWithPackage(10);
        $otherParty = InvitationParty::create(['event_id' => $otherEvent->id, 'name' => 'Private Family', 'maximum_party_size' => 2]);
        $otherParty->rsvp->personResponses()->create(['first_name' => 'Private', 'member_type' => 'adult', 'is_original_party_member' => false, 'is_attending' => true]);

        $this->actingAs($admin)->get(route('events.guests.show', [$event, $party]))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Guests/Show')
            ->has('party.members', 2)
            ->where('party.maximum_party_size', 4)
            ->has('rsvpAdditionalGuests', 1)
            ->where('rsvpAdditionalGuests.0.first_name', 'Alex')
            ->where('rsvpAdditionalGuests.0.member_type', 'child')
            ->where('rsvpAdditionalGuests.0.meal', 'Chicken')
            ->where('rsvpAdditionalGuests.0.dietary_note', 'Nut allergy')
            ->where('partyDates.created_at', 'August 11, 2026 at 11:01 AM')
            ->where('partyDates.updated_at', 'August 11, 2026 at 11:06 AM'));
    }

    private function eventWithPackage(int $maximumGuests, ?Customer $customer = null): Event
    {
        $customer ??= Customer::create(['name' => 'Customer '.uniqid()]);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => $maximumGuests, 'price' => 25, 'is_active' => true]);
        return Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Event '.uniqid(), 'event_type' => 'wedding', 'host_name' => 'Host', 'status' => 'draft']);
    }

    private function partyPayload(string $name, int $maximumPartySize): array
    {
        return ['name' => $name, 'primary_contact_name' => 'Contact', 'email' => 'contact@example.test', 'phone' => '555-0100', 'maximum_party_size' => $maximumPartySize, 'table_name' => 'A1', 'notes' => 'Internal note'];
    }

    private function memberPayload(string $firstName, string $type = 'adult'): array
    {
        return ['first_name' => $firstName, 'last_name' => 'Member', 'member_type' => $type, 'notes' => 'Note'];
    }
}
