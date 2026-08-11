<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventMealOption;
use App\Models\EventPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_editor_can_edit_and_activate_meals_for_their_event(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $editor = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = $this->event($customer);
        $event->members()->attach($editor, ['role' => 'editor']);
        $meal = $event->mealOptions()->create(['name' => 'Chicken', 'description' => 'Original', 'is_active' => true]);

        $this->actingAs($editor)
            ->put(route('events.meals.update', [$event, $meal]), ['name' => 'Fish', 'description' => 'Updated', 'display_order' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('event_meal_options', ['id' => $meal->id, 'name' => 'Fish', 'description' => 'Updated', 'display_order' => 2]);

        $this->actingAs($editor)
            ->patch(route('events.meals.active', [$event, $meal]), ['is_active' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('event_meal_options', ['id' => $meal->id, 'is_active' => false]);
    }

    public function test_meal_actions_cannot_cross_event_or_customer_boundaries(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $editor = User::factory()->create(['role' => 'customer', 'customer_id' => $customer->id]);
        $event = $this->event($customer);
        $event->members()->attach($editor, ['role' => 'editor']);
        $otherEvent = $this->event();
        $otherMeal = $otherEvent->mealOptions()->create(['name' => 'Private meal', 'is_active' => true]);

        $this->actingAs($editor)
            ->put(route('events.meals.update', [$event, $otherMeal]), ['name' => 'Attempt', 'display_order' => 0])
            ->assertNotFound();

        $this->assertDatabaseHas('event_meal_options', ['id' => $otherMeal->id, 'name' => 'Private meal']);

        $outsider = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::create(['name' => 'Other'])->id]);
        $this->actingAs($outsider)
            ->post(route('events.meals.store', $event), ['name' => 'Attempt'])
            ->assertForbidden();
    }

    private function event(?Customer $customer = null): Event
    {
        $customer ??= Customer::create(['name' => 'Customer '.uniqid()]);
        $package = EventPackage::create([
            'name' => 'Package '.uniqid(),
            'minimum_guests' => 1,
            'maximum_guests' => 50,
            'price' => 25,
            'is_active' => true,
        ]);

        return Event::create([
            'customer_id' => $customer->id,
            'event_package_id' => $package->id,
            'title' => 'Event '.uniqid(),
            'event_type' => 'wedding',
            'host_name' => 'Host',
            'status' => 'draft',
        ]);
    }
}
