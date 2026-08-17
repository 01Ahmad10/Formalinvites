<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Models\User;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\InvitationTemplateSettings;
use App\Support\PublicRsvpPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_owner_can_submit_but_only_admin_can_review_approve_publish_or_archive(): void
    {
        [$event, $owner, $admin, $support] = $this->eventWithUsers();

        $this->actingAs($owner)->post(route('events.publication.submit', $event))->assertRedirect();
        $event->refresh();
        $this->assertSame('submitted', $event->status);
        $this->assertNotNull($event->submitted_snapshot_hash);

        $this->actingAs($owner)->patch(route('events.publication.approve', $event))->assertForbidden();
        $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertForbidden();
        $this->actingAs($support)->post(route('events.publication.submit', $event))->assertForbidden();
        $this->actingAs($support)->patch(route('events.publication.under-review', $event))->assertForbidden();

        $this->actingAs($admin)->patch(route('events.publication.under-review', $event))->assertRedirect();
        $this->assertSame('under_review', $event->fresh()->status);
        $this->actingAs($admin)->patch(route('events.publication.request-changes', $event), ['review_note' => 'Please confirm the venue.'])->assertRedirect();
        $this->assertSame('changes_requested', $event->fresh()->status);
        $this->assertSame('Please confirm the venue.', $event->fresh()->review_note);
    }

    public function test_approval_and_publication_require_the_exact_submitted_and_approved_working_copy(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();

        $this->actingAs($owner)->post(route('events.publication.submit', $event))->assertRedirect();
        $submittedHash = $event->fresh()->submitted_snapshot_hash;
        $event->update(['venue' => 'Changed after submission']);
        $this->actingAs($admin)->patch(route('events.publication.approve', $event))->assertSessionHasErrors('workflow');

        $this->actingAs($owner)->post(route('events.publication.submit', $event))->assertRedirect();
        $this->assertNotSame($submittedHash, $event->fresh()->submitted_snapshot_hash);
        $this->actingAs($admin)->patch(route('events.publication.approve', $event))->assertRedirect();
        $event->update(['venue' => 'Changed after approval']);
        $this->actingAs($admin)->post(route('events.publication.publish', $event))->assertSessionHasErrors('workflow');

        $this->actingAs($owner)->post(route('events.publication.submit', $event))->assertRedirect();
        $this->actingAs($admin)->patch(route('events.publication.approve', $event))->assertRedirect();
        $this->actingAs($admin)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 1]);
    }

    public function test_direct_event_updates_cannot_bypass_the_publishing_workflow(): void
    {
        [$event, , $admin] = $this->eventWithUsers();

        $this->actingAs($admin)->put(route('events.update', $event), [
            'customer_id' => $event->customer_id,
            'event_package_id' => $event->event_package_id,
            'title' => $event->title,
            'event_type' => $event->event_type,
            'host_name' => $event->host_name,
            'event_timezone' => $event->event_timezone,
            'status' => 'published',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $event->fresh()->status);
        $this->assertDatabaseCount('event_publications', 0);
    }

    public function test_legacy_approved_event_requires_a_stage_seven_submission_before_it_can_publish(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();
        $event->update(['status' => 'approved']);

        $this->actingAs($admin)->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('workflow.working_status', 'requires_submission')
            ->where('workflow.can_submit', true)
            ->where('workflow.can_publish', false)
        );
        $this->actingAs($admin)->post(route('events.publication.publish', $event))->assertSessionHasErrors('workflow');
        $this->assertDatabaseCount('event_publications', 0);

        $this->actingAs($owner)->post(route('events.publication.submit', $event))->assertRedirect();
        $this->assertSame('submitted', $event->fresh()->status);
        $this->assertNotNull($event->fresh()->submitted_snapshot_hash);
        $this->assertNotNull($event->fresh()->submitted_at);

        $this->actingAs($admin)->patch(route('events.publication.approve', $event))->assertRedirect();
        $event->refresh();
        $this->assertSame('approved', $event->status);
        $this->assertNotNull($event->approved_snapshot_hash);
        $this->assertNotNull($event->approved_at);
        $this->assertSame($admin->id, $event->approved_by);

        $this->actingAs($admin)->post(route('events.publication.publish', $event))->assertRedirect();
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 1]);
    }

    public function test_live_invitation_uses_an_immutable_snapshot_while_working_preview_uses_current_event_data(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();
        $romantic = $this->template('romantic-floral', InvitationTemplateSettings::defaults());
        $cinematic = $this->template('modern-cinematic', InvitationTemplateSettings::modernCinematicDefaults());
        $event->update(['template_id' => $romantic->id, 'venue' => 'Cedar Hall', 'main_date' => '2026-10-12', 'start_time' => '16:00', 'rsvp_deadline' => now()->addWeek()->toDateString()]);
        $event->templateSetting()->create(['settings' => ['palette_key' => 'burgundy', 'font_pair_key' => 'classic']]);
        $event->activities()->create(['title' => 'Ceremony', 'activity_type' => 'ceremony', 'starts_at' => '2026-10-12 16:00:00+00', 'is_active' => true]);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'The Smith Family', 'maximum_party_size' => 2]);
        $this->publish($event, $owner, $admin);
        $v1 = $event->publications()->firstOrFail();

        $event->update(['template_id' => $cinematic->id, 'venue' => 'Grand Ballroom', 'main_date' => '2026-11-02', 'rsvp_deadline' => now()->subDay()->toDateString()]);
        $event->templateSetting->update(['settings' => ['palette_key' => 'midnight_gold', 'font_pair_key' => 'cinematic_serif']]);
        $event->activities()->first()->update(['title' => 'Later Ceremony', 'starts_at' => '2026-11-02 18:00:00+00']);

        $this->actingAs($owner)->get(route('events.invitation.preview', $event))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', 'modern-cinematic')
            ->where('invitation.event.venue', 'Grand Ballroom')
        );
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', 'romantic-floral')
            ->where('invitation.event.venue', 'Cedar Hall')
            ->where('invitation.event.activities.0.title', 'Ceremony')
            ->where('closed', false)
        );
        $this->assertSame('Cedar Hall', $v1->fresh()->snapshot['event']['venue']);

        $this->publish($event, $owner, $admin);
        $this->assertDatabaseHas('event_publications', ['event_id' => $event->id, 'version' => 2]);
        $this->assertSame('Cedar Hall', $v1->fresh()->snapshot['event']['venue']);
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->where('invitation.template.component_key', 'modern-cinematic')
            ->where('invitation.event.venue', 'Grand Ballroom')
            ->where('invitation.event.activities.0.title', 'Later Ceremony')
            ->where('closed', true)
        );
    }

    public function test_published_meals_remain_available_for_the_public_rsvp_after_the_working_copy_changes(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Party', 'maximum_party_size' => 1]);
        $publishedMeal = $event->mealOptions()->create(['name' => 'Chicken', 'is_active' => true]);
        $this->publish($event, $owner, $admin);
        $publishedMeal->update(['name' => 'Working Copy Fish', 'is_active' => false]);
        $event->mealOptions()->create(['name' => 'Working Copy Vegetarian', 'is_active' => true]);
        $mealKey = app(PublicRsvpPayload::class)->invitationMeals($party, $event->latestPublication())[0]['id'];

        $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
            ->where('meals.0.name', 'Chicken')
            ->has('meals', 1)
        );
        $this->from(route('public.invitation.show', $party->rsvp_token))->post(route('public.rsvp.submit', $party->rsvp_token), ['status' => 'attending', 'additional_guests' => [['first_name' => 'Guest', 'member_type' => 'adult', 'event_meal_option_id' => $mealKey]]])->assertRedirect(route('public.invitation.show', $party->rsvp_token));
        $this->assertSame($publishedMeal->id, $party->fresh()->rsvp->personResponses()->first()->event_meal_option_id);
    }

    public function test_public_token_requires_a_publication_and_archived_events_are_unavailable(): void
    {
        [$event, $owner, $admin] = $this->eventWithUsers();
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Party', 'maximum_party_size' => 1]);
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertNotFound();
        $this->publish($event, $owner, $admin);
        $this->actingAs($admin)->patch(route('events.publication.archive', $event))->assertRedirect();
        $this->get(route('public.invitation.show', $party->rsvp_token))->assertNotFound();
        $this->assertDatabaseCount('event_publications', 1);
    }

    private function publish(Event $event, User $owner, User $admin): void
    {
        $this->actingAs($owner)->post(route('events.publication.submit', $event))->assertRedirect();
        $this->actingAs($admin)->patch(route('events.publication.approve', $event))->assertRedirect();
        $this->actingAs($admin)->post(route('events.publication.publish', $event))->assertRedirect();
    }

    private function eventWithUsers(): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $support = User::factory()->create(['role' => 'support']);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 25, 'is_active' => true]);
        $event = Event::create(['customer_id' => $customer->id, 'event_package_id' => $package->id, 'title' => 'Event '.uniqid(), 'event_type' => 'wedding', 'host_name' => 'Maya', 'event_timezone' => 'America/New_York', 'status' => 'draft']);
        $event->members()->attach($owner, ['role' => 'owner']);

        return [$event, $owner, $admin, $support];
    }

    private function template(string $componentKey, array $settings): Template
    {
        return Template::create(['name' => str($componentKey)->headline(), 'slug' => $componentKey.'-'.uniqid(), 'component_key' => $componentKey, 'default_settings' => $settings, 'is_active' => true]);
    }
}
