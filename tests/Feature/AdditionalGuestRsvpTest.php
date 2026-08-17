<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventMealOption;
use App\Models\EventPackage;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use App\Models\RsvpPersonResponse;
use App\Support\InvitationPublicationSnapshotBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdditionalGuestRsvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_with_two_listed_members_and_maximum_four_can_submit_zero_one_or_two_additional_guests(): void
    {
        $event = $this->event();
        $meal = $event->mealOptions()->create(['name' => 'Chicken', 'is_active' => true]);
        $this->publish($event);

        $zero = $this->partyWithTwoMembers($event, 'Zero');
        $this->get(route('public.rsvp.show', $zero->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicRsvp')
            ->where('party.maximum_party_size', 4)
            ->where('party.listed_member_count', 2)
            ->has('party.members', 2));
        $this->post(route('public.rsvp.submit', $zero->rsvp_token), $this->payload($zero))->assertRedirect();
        $this->assertSame(0, RsvpPersonResponse::where('rsvp_id', $zero->fresh()->rsvp->id)->where('is_original_party_member', false)->count());

        $one = $this->partyWithTwoMembers($event, 'One');
        $this->post(route('public.rsvp.submit', $one->rsvp_token), $this->payload($one, [[
            'first_name' => 'Alex', 'last_name' => 'Williams', 'member_type' => 'adult', 'event_meal_option_id' => $meal->id, 'dietary_note' => 'Nut allergy',
        ]]))->assertRedirect();
        $this->assertDatabaseHas('rsvp_person_responses', ['rsvp_id' => $one->fresh()->rsvp->id, 'first_name' => 'Alex', 'member_type' => 'adult', 'is_original_party_member' => false, 'dietary_note' => 'Nut allergy']);

        $two = $this->partyWithTwoMembers($event, 'Two');
        $this->post(route('public.rsvp.submit', $two->rsvp_token), $this->payload($two, [
            ['first_name' => 'Alex', 'last_name' => 'Williams', 'member_type' => 'adult', 'event_meal_option_id' => $meal->id, 'dietary_note' => null],
            ['first_name' => 'Sam', 'last_name' => 'Williams', 'member_type' => 'child', 'event_meal_option_id' => $meal->id, 'dietary_note' => 'No dairy'],
        ]))->assertRedirect();
        $this->assertSame(2, RsvpPersonResponse::where('rsvp_id', $two->fresh()->rsvp->id)->where('is_original_party_member', false)->count());
        $this->assertDatabaseHas('rsvp_person_responses', ['rsvp_id' => $two->fresh()->rsvp->id, 'first_name' => 'Sam', 'member_type' => 'child', 'dietary_note' => 'No dairy']);
    }

    public function test_three_additional_guests_and_another_events_meal_are_rejected(): void
    {
        $event = $this->event();
        $party = $this->partyWithTwoMembers($event, 'Capacity');
        $this->publish($event);
        $threeGuests = [
            ['first_name' => 'One', 'member_type' => 'adult'],
            ['first_name' => 'Two', 'member_type' => 'adult'],
            ['first_name' => 'Three', 'member_type' => 'child'],
        ];

        $this->post(route('public.rsvp.submit', $party->rsvp_token), $this->payload($party, $threeGuests))->assertSessionHasErrors('additional_guests');

        $otherMeal = $this->event()->mealOptions()->create(['name' => 'Private meal', 'is_active' => true]);
        $this->post(route('public.rsvp.submit', $party->rsvp_token), $this->payload($party, [[
            'first_name' => 'Alex', 'member_type' => 'adult', 'event_meal_option_id' => $otherMeal->id,
        ]]))->assertSessionHasErrors('members');
    }

    public function test_editing_reloads_removes_and_does_not_duplicate_additional_guests_then_declining_clears_them(): void
    {
        $event = $this->event();
        $party = $this->partyWithTwoMembers($event, 'Update');
        $this->publish($event);
        $initial = [
            ['first_name' => 'Alex', 'last_name' => 'Williams', 'member_type' => 'adult'],
            ['first_name' => 'Sam', 'last_name' => 'Williams', 'member_type' => 'child'],
        ];

        $this->post(route('public.rsvp.submit', $party->rsvp_token), $this->payload($party, $initial))->assertRedirect();
        $this->get(route('public.rsvp.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicRsvp')
            ->has('rsvp.person_responses', 4)
            ->where('rsvp.person_responses.2.is_original_party_member', false)
            ->where('rsvp.person_responses.3.member_type', 'child'));

        $this->post(route('public.rsvp.submit', $party->rsvp_token), $this->payload($party, [$initial[0]]))->assertRedirect();
        $rsvpId = $party->fresh()->rsvp->id;
        $this->assertSame(1, RsvpPersonResponse::where('rsvp_id', $rsvpId)->where('is_original_party_member', false)->count());
        $this->assertDatabaseHas('rsvp_person_responses', ['rsvp_id' => $rsvpId, 'first_name' => 'Alex', 'is_original_party_member' => false]);

        $this->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending'])->assertRedirect();
        $this->assertSame(0, RsvpPersonResponse::where('rsvp_id', $rsvpId)->where('is_attending', true)->where('is_original_party_member', false)->count());
    }

    private function payload(InvitationParty $party, array $additionalGuests = []): array
    {
        return [
            'status' => 'attending',
            'members' => $party->members->map(fn ($member) => ['id' => $member->id, 'is_attending' => true])->values()->all(),
            'additional_guests' => $additionalGuests,
        ];
    }

    private function partyWithTwoMembers(Event $event, string $name): InvitationParty
    {
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => $name, 'maximum_party_size' => 4]);
        $party->members()->createMany([
            ['first_name' => 'Taylor', 'last_name' => 'Williams', 'member_type' => 'adult'],
            ['first_name' => 'Jordan', 'last_name' => 'Williams', 'member_type' => 'child'],
        ]);

        return $party->load('members');
    }

    private function event(): Event
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
            'rsvp_deadline' => now()->addWeek()->toDateString(),
        ]);
    }

    private function publish(Event $event): EventPublication
    {
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
