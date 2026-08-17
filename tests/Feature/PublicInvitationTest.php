<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\EventPublication;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Models\User;
use App\Support\InvitationTemplateSettings;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\PublicRsvpPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_invitation_uses_only_its_own_secure_token_and_safe_data(): void
    {
        [$event, $template] = $this->eventWithRomanticTemplate();
        $event->update(['main_date' => '2026-08-12', 'start_time' => '16:00', 'end_time' => '17:00', 'event_timezone' => 'Asia/Beirut', 'venue' => 'Rose Garden']);
        $event->templateSetting()->create(['settings' => ['palette_key' => 'burgundy', 'font_pair_key' => 'classic']]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Williams Family', 'maximum_party_size' => 2]);
        $party->members()->create(['first_name' => 'Michael', 'last_name' => 'Williams', 'member_type' => 'adult']);
        $other = InvitationParty::create(['event_id' => $event->id, 'name' => 'Private Party', 'maximum_party_size' => 1]);
        $event->activities()->create(['title' => 'Ceremony', 'activity_type' => 'ceremony', 'starts_at' => '2026-08-12 13:00:00+00', 'is_active' => true]);
        $event->activities()->create(['title' => 'Private planning', 'activity_type' => 'other', 'starts_at' => '2026-08-12 14:00:00+00', 'is_active' => false]);
        $this->publish($event);

        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicInvitation')
            ->where('invitation.template.component_key', 'romantic-floral')
            ->where('invitation.settings.palette_key', 'burgundy')
            ->where('invitation.settings.primary_color', '#7C2438')
            ->where('invitation.settings.font_pair_key', 'classic')
            ->where('invitation.event.start_time', '4:00 PM')
            ->where('invitation.party_name', 'Williams Family')
            ->where('party.name', 'Williams Family')
            ->where('party.members.0.first_name', 'Michael')
            ->has('invitation.event.activities', 1)
            ->missing('invitation.event.customer_id')
            ->missing('invitation.event.payment')
            ->missing('invitation.event.package')
            ->missing('party.event.id')
            ->missing('party.event.customer')
        );
        $this->get(route('public.invitation.show', $other->rsvp_token))->assertInertia(fn (Assert $page) => $page->where('party.name', 'Private Party')->where('invitation.party_name', 'Private Party'));
        $this->assertNotSame($party->members->first()->id, $this->opaqueMemberKey($party));
        $this->assertSame($template->id, $event->fresh()->template_id);
    }

    public function test_invalid_and_regenerated_invitation_tokens_fail_safely(): void
    {
        [$event] = $this->eventWithRomanticTemplate();
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Party', 'maximum_party_size' => 1]);
        $this->publish($event);
        $admin = User::factory()->create(['role' => 'admin']);
        $oldToken = $party->rsvp_token;

        $this->get(route('public.invitation.show', 'not-a-real-token'))->assertNotFound();
        $this->actingAs($admin)->patch(route('events.guests.rsvp-token', [$event, $party]))->assertRedirect();
        $party->refresh();
        $this->get(route('public.invitation.show', $oldToken))->assertNotFound();
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertOk();
    }

    public function test_editorial_luxury_public_invitation_uses_safe_event_data_and_keeps_rsvp_inside_the_invitation(): void
    {
        $customer = Customer::create(['name' => 'Editorial Customer']);
        $package = EventPackage::create(['name' => 'Editorial Package', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $template = Template::create(['name' => 'Editorial Luxury', 'slug' => 'editorial-'.uniqid(), 'component_key' => 'editorial-luxury', 'default_settings' => InvitationTemplateSettings::editorialLuxuryDefaults(), 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'template_id' => $template->id, 'title' => 'The Edit', 'event_type' => 'wedding', 'host_name' => 'Avery', 'second_host_name' => 'Morgan', 'event_timezone' => 'America/New_York', 'main_date' => '2026-09-12', 'status' => 'draft', 'rsvp_deadline' => now()->addWeek()->toDateString(), 'venue' => 'The Gallery', 'guest_information' => 'Arrive fifteen minutes early.']);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'The Lee Family', 'maximum_party_size' => 1]);
        $event->activities()->create(['title' => 'Private Ceremony', 'activity_type' => 'ceremony', 'starts_at' => '2026-09-12 19:00:00+00', 'is_active' => true]);
        $event->activities()->create(['title' => 'Internal planning', 'activity_type' => 'other', 'starts_at' => '2026-09-12 20:00:00+00', 'is_active' => false]);
        $this->publish($event);

        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicInvitation')
            ->where('invitation.template.component_key', 'editorial-luxury')
            ->where('invitation.party_name', 'The Lee Family')
            ->where('invitation.settings.palette_key', 'champagne_noir')
            ->where('invitation.settings.font_pair_key', 'editorial')
            ->has('invitation.event.activities', 1)
            ->missing('invitation.event.customer_id')
            ->missing('invitation.event.payment')
            ->missing('invitation.event.package')
        );

        $this->from(route('public.invitation.show', $party->rsvp_token))
            ->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending', 'guest_message' => 'Regretfully unavailable.'])
            ->assertRedirect(route('public.invitation.show', $party->rsvp_token));
        $this->from(route('public.invitation.show', $party->rsvp_token))
            ->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending', 'guest_message' => 'Updated response.'])
            ->assertRedirect(route('public.invitation.show', $party->rsvp_token));

        $this->assertSame(1, $party->fresh()->rsvp()->count());
        $this->assertSame('Updated response.', $party->fresh()->rsvp->guest_message);
    }

    public function test_modern_cinematic_public_invitation_uses_safe_data_active_activities_and_the_existing_rsvp_lifecycle(): void
    {
        $customer = Customer::create(['name' => 'Cinematic Customer']);
        $package = EventPackage::create(['name' => 'Cinematic Package', 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $template = Template::create(['name' => 'Modern Cinematic', 'slug' => 'cinematic-'.uniqid(), 'component_key' => 'modern-cinematic', 'default_settings' => InvitationTemplateSettings::modernCinematicDefaults(), 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'template_id' => $template->id, 'title' => 'An Evening in Motion', 'event_type' => 'engagement', 'host_name' => 'Maya', 'second_host_name' => 'Elias', 'event_timezone' => 'America/New_York', 'main_date' => '2026-09-12', 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'draft', 'rsvp_deadline' => now()->addWeek()->toDateString(), 'venue' => 'The Observatory']);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'The Smith Family', 'maximum_party_size' => 1]);
        $event->activities()->create(['title' => 'Ceremony', 'activity_type' => 'ceremony', 'starts_at' => '2026-09-12 20:00:00+00', 'is_active' => true]);
        $event->activities()->create(['title' => 'Internal planning', 'activity_type' => 'other', 'starts_at' => '2026-09-12 21:00:00+00', 'is_active' => false]);
        $this->publish($event);

        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicInvitation')
            ->where('invitation.template.component_key', 'modern-cinematic')
            ->where('invitation.settings.palette_key', 'midnight_gold')
            ->where('invitation.settings.font_pair_key', 'cinematic_serif')
            ->where('invitation.party_name', 'The Smith Family')
            ->where('invitation.event.start_time', '4:00 PM')
            ->has('invitation.event.activities', 1)
            ->where('invitation.event.activities.0.title', 'Ceremony')
            ->where('invitation.experience.intro_video', null)
            ->where('invitation.experience.audio', null)
            ->missing('invitation.event.customer_id')
            ->missing('invitation.event.package')
            ->missing('invitation.event.payment')
        );

        $this->from(route('public.invitation.show', $party->rsvp_token))
            ->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending', 'guest_message' => 'We will celebrate from afar.'])
            ->assertRedirect(route('public.invitation.show', $party->rsvp_token));
        $this->from(route('public.invitation.show', $party->rsvp_token))
            ->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending', 'guest_message' => 'Updated cinematic response.'])
            ->assertRedirect(route('public.invitation.show', $party->rsvp_token));

        $this->assertSame(1, $party->fresh()->rsvp()->count());
        $this->assertSame('Updated cinematic response.', $party->fresh()->rsvp->guest_message);
    }

    public function test_public_invitation_can_submit_and_update_its_existing_rsvp_with_opaque_member_keys(): void
    {
        [$event] = $this->eventWithRomanticTemplate();
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Party', 'maximum_party_size' => 2]);
        $member = $party->members()->create(['first_name' => 'Nadia', 'member_type' => 'adult']);
        $meal = $event->mealOptions()->create(['name' => 'Chicken', 'is_active' => true]);
        $this->publish($event);
        $payload = app(PublicRsvpPayload::class);
        $publicMember = $payload->invitationParty($party)['members'][0]['id'];
        $publicMeal = $payload->invitationMeals($party)[0]['id'];

        $response = $this->from(route('public.invitation.show', $party->rsvp_token))->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'attending', 'guest_message' => 'Looking forward to celebrating.', 'members' => [['id' => $publicMember, 'is_attending' => true, 'event_meal_option_id' => $publicMeal, 'dietary_note' => 'Nut allergy']], 'additional_guests' => [['first_name' => 'Guest', 'last_name' => 'Child', 'member_type' => 'child', 'event_meal_option_id' => $publicMeal, 'dietary_note' => 'No dairy']]]);
        $response->assertRedirect(route('public.invitation.show', $party->rsvp_token));
        $this->assertNotSame(route('public.rsvp.show', $party->rsvp_token), $response->headers->get('Location'));
        $this->assertSame('attending', $party->fresh()->rsvp->status);
        $this->assertCount(2, $party->fresh()->rsvp->personResponses);
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicInvitation')
            ->where('rsvp.status', 'attending')
            ->where('rsvp.guest_message', 'Looking forward to celebrating.')
            ->where('rsvp.person_responses.0.meal_option.name', 'Chicken')
            ->where('rsvp.person_responses.0.dietary_note', 'Nut allergy')
            ->where('rsvp.person_responses.1.member_type', 'child')
            ->where('rsvp.person_responses.1.meal_option.name', 'Chicken')
            ->where('rsvp.person_responses.1.dietary_note', 'No dairy')
            ->where('closed', false)
        );

        $this->from(route('public.invitation.show', $party->rsvp_token))->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'attending', 'guest_message' => 'Updated message', 'members' => [['id' => $publicMember, 'is_attending' => false]], 'additional_guests' => []])->assertRedirect(route('public.invitation.show', $party->rsvp_token));
        $this->assertSame(1, $party->fresh()->rsvp->personResponses()->count());
        $this->assertSame($member->id, $party->fresh()->rsvp->personResponses()->first()->party_member_id);
        $this->assertSame(1, $party->fresh()->rsvp()->count());
        $this->assertNotSame($meal->id, $publicMeal);
    }

    public function test_legacy_rsvp_page_remains_usable_after_submission(): void
    {
        [$event] = $this->eventWithRomanticTemplate();
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Legacy party', 'maximum_party_size' => 1]);
        $this->publish($event);

        $this->from(route('public.rsvp.show', $party->rsvp_token))
            ->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'not_attending'])
            ->assertRedirect(route('public.rsvp.show', $party->rsvp_token));

        $this->get(route('public.rsvp.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicRsvp')
            ->where('rsvp.status', 'not_attending')
        );
    }

    public function test_public_invitation_keeps_a_submitted_rsvp_read_only_after_the_deadline(): void
    {
        [$event] = $this->eventWithRomanticTemplate();
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Closed party', 'maximum_party_size' => 1]);
        $party->rsvp->update(['status' => 'attending', 'submitted_at' => now(), 'last_updated_at' => now()]);
        $event->update(['rsvp_deadline' => now()->subDay()->toDateString()]);
        $this->publish($event);

        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->component('PublicInvitation')
            ->where('rsvp.status', 'attending')
            ->where('closed', true)
        );
    }

    private function eventWithRomanticTemplate(): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $template = Template::create(['name' => 'Romantic Floral', 'slug' => 'romantic-'.uniqid(), 'component_key' => 'romantic-floral', 'default_settings' => InvitationTemplateSettings::defaults(), 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'template_id' => $template->id, 'title' => 'Nadia and Sam Wedding', 'event_type' => 'wedding', 'host_name' => 'Nadia', 'second_host_name' => 'Sam', 'event_timezone' => 'Asia/Beirut', 'status' => 'draft', 'rsvp_deadline' => now()->addWeek()->toDateString()]);

        return [$event, $template];
    }

    private function opaqueMemberKey(InvitationParty $party): string
    {
        return app(PublicRsvpPayload::class)->invitationParty($party)['members'][0]['id'];
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
