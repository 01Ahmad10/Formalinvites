<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\InvitationParty;
use App\Models\Template;
use App\Models\User;
use App\Support\InvitationTemplateSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_reference_media_never_enters_production_payloads_or_snapshots(): void
    {
        [$event] = $this->event();
        $this->app->instance('env', 'production');
        $this->assertSame([], \App\Support\LocalInvitationMedia::forTemplate('romantic-floral'));
        $payload = app(\App\Support\InvitationPresenter::class)->present($event);
        $this->assertArrayNotHasKey('media', $payload);
        $this->assertArrayNotHasKey('reference_demo', $payload);
        $this->assertStringNotContainsString('reference-template-assets', json_encode(app(\App\Support\InvitationPublicationSnapshotBuilder::class)->build($event)));
    }

    public function test_all_six_templates_share_real_family_meals_rsvp_and_snapshot_behavior(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach (['romantic-floral', 'editorial-luxury', 'modern-cinematic', 'dolce-vita', 'blossom-oud', 'sacred-garden'] as $key) {
            [$event, $owner] = $this->event();
            $event->template->update(['component_key' => $key]);
            $party = $event->invitationParties()->create(['name' => 'Real Family '.$key, 'maximum_party_size' => 3, 'is_active' => true]);
            $member = $party->members()->create(['first_name' => 'Real Guest', 'last_name' => 'Family', 'member_type' => 'adult']);
            $meal = $event->mealOptions()->create(['name' => 'Real vegetarian meal', 'is_active' => true]);
            $event->mealOptions()->create(['name' => 'Hidden meal', 'is_active' => false]);
            $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertSessionHasNoErrors();
            $first = $event->latestPublication();
            $hash = $first->snapshot_hash;
            $this->get(route('events.builder', $event))->assertInertia(fn (Assert $page) => $page
                ->where('previewParties.0.members.0.first_name', 'Real Guest')
                ->where('previewParties.0.maximum_party_size', 3)
                ->missing('previewParties.0.rsvp_token'));
            $this->get(route('events.invitation.preview', ['event' => $event, 'party_id' => $party->id]))->assertInertia(fn (Assert $page) => $page
                ->where('invitation.preview_context.party.members.0.id', $member->id)
                ->has('invitation.preview_context.meals', 1)->missing('invitation.reference_demo')->missing('invitation.media'));
            $this->assertSame(1, $event->publications()->count());
            $payload = $this->payload($event, ['title' => 'Real saved '.$key, 'content' => [...$this->content($event), 'story_enabled' => true, 'story_body' => 'Retained story']]);
            $this->put(route('events.builder.update', $event), $payload)->assertSessionHasNoErrors();
            $this->assertSame(2, $event->publications()->count());
            $this->put(route('events.builder.update', $event), $payload)->assertSessionHasNoErrors();
            $this->assertSame(2, $event->publications()->count());
            $payload['content']['story_enabled'] = false;
            $this->put(route('events.builder.update', $event), $payload)->assertSessionHasNoErrors();
            $this->assertSame('Retained story', $event->fresh()->invitationContent->story_body);
            $this->get(route('public.invitation.show', $party->rsvp_token))->assertInertia(fn (Assert $page) => $page
                ->where('invitation.template.component_key', $key)->where('invitation.party_name', 'Real Family '.$key)
                ->where('invitation.content.story_enabled', false)->where('party.members.0.first_name', 'Real Guest')
                ->has('meals', 1)->missing('invitation.reference_demo')->missing('invitation.media'));
            $rsvp = app(\App\Support\PublicRsvpPayload::class);
            $memberKey = $rsvp->invitationParty($party)['members'][0]['id'];
            $mealKey = $rsvp->invitationMeals($party)[0]['id'];
            $response = ['status' => 'attending', 'guest_message' => 'Looking forward to it', 'members' => [['id' => $memberKey, 'is_attending' => true, 'event_meal_option_id' => $mealKey, 'dietary_note' => 'No nuts']], 'additional_guests' => [['first_name' => 'Extra', 'last_name' => 'Guest', 'member_type' => 'adult', 'event_meal_option_id' => $mealKey, 'dietary_note' => 'No dairy']]];
            $this->post(route('public.rsvp.submit', $party->rsvp_token), $response)->assertSessionHasNoErrors();
            $this->assertSame('attending', $party->fresh()->rsvp->status);
            $this->assertSame(2, $party->fresh()->rsvp->personResponses()->where('is_attending', true)->count());
            $this->assertSame(2, $party->fresh()->rsvp->personResponses()->where('event_meal_option_id', $meal->id)->count());
            $response['status'] = 'not_attending';
            $this->post(route('public.rsvp.submit', $party->rsvp_token), $response)->assertSessionHasNoErrors();
            $this->assertSame('not_attending', $party->fresh()->rsvp->status);
            $response['status'] = 'attending';
            $this->post(route('public.rsvp.submit', $party->rsvp_token), $response)->assertSessionHasNoErrors();
            $this->assertSame('No nuts', $party->fresh()->rsvp->personResponses()->where('party_member_id', $member->id)->first()->dietary_note);
            $this->assertSame($hash, $first->fresh()->snapshot_hash);
            $this->assertStringNotContainsString('template-demos', json_encode($event->latestPublication()->snapshot));
        }
    }

    public function test_customer_can_open_the_builder_for_their_live_event_and_preview_current_data(): void
    {
        [$event, $owner] = $this->event(true);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Haddad Family', 'maximum_party_size' => 4]);

        $this->actingAs($owner)->get(route('events.builder', $event))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Builder')
            ->where('event.id', $event->id)
            ->where('previewInvitation.event.title', $event->title)
            ->has('templates', 1)
            ->has('activities', 0)
            ->has('meals', 0)
            ->where('previewParties.0.id', $party->id)
            ->where('previewParties.0.name', 'Haddad Family')
        );
        $this->assertDatabaseCount('invitation_parties', 1);
    }

    public function test_builder_preview_party_options_are_scoped_and_do_not_mutate_any_records(): void
    {
        [$event, $owner] = $this->event(true);
        $party = InvitationParty::create(['event_id' => $event->id, 'name' => 'Haddad Family', 'maximum_party_size' => 3]);
        [$foreign] = $this->event(true);
        InvitationParty::create(['event_id' => $foreign->id, 'name' => 'Private Family', 'maximum_party_size' => 2]);

        $this->actingAs($owner)->get(route('events.builder', $event))->assertInertia(fn (Assert $page) => $page
            ->has('previewParties', 1)
            ->where('previewParties.0.name', 'Haddad Family')
            ->missing('previewParties.1')
        );
        $this->assertDatabaseCount('event_publications', 2);
        $this->assertDatabaseCount('rsvps', 2);
        $this->assertSame('Haddad Family', app(\App\Support\InvitationPresenter::class)->present($event, $party)['party_name']);
    }

    public function test_ready_draft_events_can_enter_final_personalization_and_archived_events_stay_review_only(): void
    {
        [$setup, $owner] = $this->event();
        $this->actingAs($owner)->get(route('events.builder', $setup))->assertInertia(fn (Assert $page) => $page
            ->component('Events/Builder')
            ->where('isLive', false)
        );

        [$archived, $archivedOwner] = $this->event(true);
        $archived->update(['status' => 'archived']);
        $this->actingAs($archivedOwner)->get(route('events.builder', $archived))->assertRedirect(route('events.show', $archived));
        $this->actingAs($archivedOwner)->put(route('events.builder.update', $archived), $this->payload($archived))->assertStatus(422);
    }

    public function test_customer_cannot_open_or_save_another_customers_builder(): void
    {
        [$event] = $this->event(true);
        [, $other] = $this->event(true);

        $this->actingAs($other)->get(route('events.builder', $event))->assertForbidden();
        $this->actingAs($other)->put(route('events.builder.update', $event), $this->payload($event))->assertForbidden();
    }

    public function test_builder_save_preserves_customer_only_fields_and_creates_one_snapshot_per_changed_save(): void
    {
        [$event, $owner] = $this->event(true, ['guest_capacity' => 42]);
        $originalCustomer = $event->customer_id;
        $payload = $this->payload($event, ['title' => 'Updated Builder Event', 'guest_capacity' => 999, 'customer_id' => 999]);

        $this->actingAs($owner)->put(route('events.builder.update', $event), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Updated Builder Event', $event->fresh()->title);
        $this->assertSame(42, $event->fresh()->guest_capacity);
        $this->assertSame($originalCustomer, $event->fresh()->customer_id);
        $this->assertDatabaseCount('event_publications', 2);

        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event->fresh(), ['title' => 'Updated Builder Event']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('event_publications', 2);
    }

    public function test_builder_persists_optional_content_and_gift_methods_in_the_live_snapshot(): void
    {
        [$event, $owner] = $this->event(true);
        $payload = $this->payload($event, [
            'content' => [
                'primary_locale' => 'ar', 'story_enabled' => true, 'story_heading' => 'قصتنا', 'story_body' => 'A lasting story.',
                'gift_registry_enabled' => true, 'gift_registry_intro' => 'Your presence is our gift.',
                'ending_enabled' => true, 'ending_title' => 'With love', 'ending_message' => 'See you soon.',
            ],
            'gift_methods' => [['label' => 'Registry', 'details' => 'Example registry', 'external_url' => 'https://example.test/registry', 'display_order' => 0, 'is_active' => true]],
        ]);

        $this->actingAs($owner)->put(route('events.builder.update', $event), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('event_invitation_contents', ['event_id' => $event->id, 'primary_locale' => 'ar', 'story_enabled' => true, 'ending_enabled' => true]);
        $this->assertDatabaseHas('event_gift_methods', ['event_id' => $event->id, 'label' => 'Registry', 'is_active' => true]);
        $snapshot = $event->latestPublication()->snapshot;
        $this->assertSame('قصتنا', $snapshot['content']['story_heading']);
        $this->assertSame('Registry', $snapshot['gift_methods'][0]['label']);
        $this->assertDatabaseCount('event_publications', 2);
    }

    public function test_builder_rejects_another_events_gift_method_and_hiding_sections_preserves_data(): void
    {
        [$event, $owner] = $this->event(true);
        [$other] = $this->event(true);
        $foreignMethod = $other->giftMethods()->create(['label' => 'Private', 'display_order' => 0, 'is_active' => true]);
        $ownMethod = $event->giftMethods()->create(['label' => 'Keep me', 'display_order' => 0, 'is_active' => true]);
        $event->invitationContent()->create(['story_enabled' => true, 'story_heading' => 'Keep story', 'gift_registry_enabled' => true, 'ending_enabled' => true, 'ending_title' => 'Keep ending', 'ending_message' => 'Keep this message']);

        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event, ['gift_methods' => [['id' => $foreignMethod->id, 'label' => 'Private', 'is_active' => true]]]))->assertSessionHasErrors('gift_methods');
        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event, [
            'content' => [...$this->content($event), 'story_enabled' => false, 'gift_registry_enabled' => false, 'ending_enabled' => false],
            'gift_methods' => [['id' => $ownMethod->id, 'label' => 'Keep me', 'details' => null, 'external_url' => null, 'display_order' => 0, 'is_active' => true]],
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('event_invitation_contents', ['event_id' => $event->id, 'story_enabled' => false, 'story_heading' => 'Keep story', 'gift_registry_enabled' => false, 'ending_enabled' => false, 'ending_title' => 'Keep ending', 'ending_message' => 'Keep this message']);
        $this->assertDatabaseHas('event_gift_methods', ['id' => $ownMethod->id, 'label' => 'Keep me']);
    }

    public function test_builder_enforces_template_deadline_and_overnight_rules(): void
    {
        [$event, $owner] = $this->event(true);
        $inactive = Template::create(['name' => 'Inactive', 'slug' => 'inactive', 'component_key' => 'modern-minimal', 'default_settings' => [], 'is_active' => false, 'is_customer_selectable' => true]);

        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event, ['template_id' => $inactive->id]))->assertSessionHasErrors('template_id');
        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event, ['rsvp_deadline' => '2027-06-11']))->assertSessionHasErrors('rsvp_deadline');
        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event, ['end_time' => '03:00']))->assertSessionHasNoErrors();
        $this->assertSame('2027-06-11', $event->fresh()->end_date->format('Y-m-d'));
    }

    public function test_changing_template_saves_the_renderer_and_one_immutable_live_version(): void
    {
        [$event, $owner] = $this->event(true);
        $original = $event->latestPublication();
        $template = Template::create(['name' => 'Cinematic', 'slug' => 'cinematic', 'component_key' => 'modern-cinematic', 'default_settings' => InvitationTemplateSettings::modernCinematicDefaults(), 'is_active' => true, 'is_customer_selectable' => true]);

        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event, ['template_id' => $template->id]))->assertSessionHasNoErrors();
        $this->assertSame($template->id, $event->fresh()->template_id);
        $this->assertSame('modern-cinematic', $event->latestPublication()->snapshot['template']['component_key']);
        $this->assertSame('romantic-floral', $original->fresh()->snapshot['template']['component_key']);
        $this->assertSame(2, $event->publications()->count());
        $this->actingAs($owner)->put(route('events.builder.update', $event), $this->payload($event->fresh()))->assertSessionHasNoErrors();
        $this->assertSame(2, $event->publications()->count());
    }

    public function test_information_step_updates_the_same_event_and_enters_personalization(): void
    {
        [$event, $owner] = $this->event();
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'information']), $this->payload($event, ['main_date' => null, 'start_time' => null, 'end_time' => null]))
            ->assertSessionHasErrors(['main_date', 'start_time']);
        $this->assertSame('2027-06-10', $event->fresh()->main_date->format('Y-m-d'));
        $this->actingAs($owner)->patch(route('events.setup.save', [$event, 'information']), $this->payload($event, ['title' => 'Our celebration']))
            ->assertSessionHasNoErrors()->assertRedirect(route('events.builder', $event));
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('event_publications', 0);
        $this->assertSame('Our celebration', $event->fresh()->title);
    }

    private function event(bool $live = false, array $overrides = []): array
    {
        $customer = Customer::create(['name' => 'Customer '.uniqid()]);
        $owner = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $package = EventPackage::create(['name' => 'Package '.uniqid(), 'minimum_guests' => 1, 'maximum_guests' => 50, 'price' => 100, 'is_active' => true]);
        $template = Template::create(['name' => 'Romantic', 'slug' => 'romantic-'.uniqid(), 'component_key' => 'romantic-floral', 'default_settings' => InvitationTemplateSettings::defaults(), 'is_active' => true, 'is_customer_selectable' => true]);
        $event = Event::create([...[ 'customer_id' => $customer->id, 'event_package_id' => $package->id, 'template_id' => $template->id, 'title' => 'Builder Event', 'event_type' => 'wedding', 'host_name' => 'Maya', 'main_date' => '2027-06-10', 'start_time' => '18:00', 'event_timezone' => 'America/New_York', 'rsvp_deadline' => '2027-06-09', 'status' => 'draft' ], ...$overrides]);
        $event->templateSetting()->create(['settings' => []]);
        $event->members()->attach($owner, ['role' => 'owner']);
        if ($live) $this->actingAs($owner)->post(route('events.publication.publish', $event))->assertRedirect();

        return [$event, $owner];
    }

    private function payload(Event $event, array $overrides = []): array
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
            'content' => $this->content($event),
            'gift_methods' => $event->giftMethods()->orderBy('display_order')->get()->map(fn ($method) => ['id' => $method->id, 'label' => $method->label, 'details' => $method->details, 'external_url' => $method->external_url, 'display_order' => $method->display_order, 'is_active' => $method->is_active])->all(),
        ], ...$overrides];
    }

    private function content(Event $event): array
    {
        $content = $event->invitationContent;

        return ['primary_locale' => $content?->primary_locale ?? 'en', 'story_enabled' => (bool) $content?->story_enabled, 'story_heading' => $content?->story_heading, 'story_body' => $content?->story_body, 'gift_registry_enabled' => (bool) $content?->gift_registry_enabled, 'gift_registry_intro' => $content?->gift_registry_intro, 'ending_enabled' => (bool) $content?->ending_enabled, 'ending_title' => $content?->ending_title, 'ending_message' => $content?->ending_message];
    }
}
