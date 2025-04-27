<?php

use App\Models\Event;
use App\Models\EventCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('admin can create an event', function () {
    $admin = $this->actingAsAdmin();
    $category = EventCategory::factory()->create();

    $response = $this->postJson('/api/events', [
        'name' => 'Test Event',
        'description' => 'Test Description',
        'start_time' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
        'end_time' => Carbon::tomorrow()->addHours(2)->format('Y-m-d H:i:s'),
        'max_participants' => 50,
        'is_private' => false,
        'category_id' => $category->id,
        'timezone' => 'UTC'
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'Event created successfully'
        ]);
});

test('organizer can create an event', function () {
    $organizer = $this->actingAsOrganizer();
    $category = EventCategory::factory()->create();

    $response = $this->postJson('/api/events', [
        'name' => 'Test Event',
        'description' => 'Test Description',
        'start_time' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
        'end_time' => Carbon::tomorrow()->addHours(2)->format('Y-m-d H:i:s'),
        'max_participants' => 50,
        'is_private' => false,
        'category_id' => $category->id,
        'timezone' => 'UTC'
    ]);

    $response->assertStatus(201);
});

test('regular user cannot create an event', function () {
    $user = $this->actingAsUser();
    $category = EventCategory::factory()->create();

    $response = $this->postJson('/api/events', [
        'name' => 'Test Event',
        'description' => 'Test Description',
        'start_time' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
        'end_time' => Carbon::tomorrow()->addHours(2)->format('Y-m-d H:i:s'),
        'max_participants' => 50,
        'category_id' => $category->id,
        'timezone' => 'UTC'
    ]);

    $response->assertStatus(403);
});

test('event creation fails with missing required fields', function () {
    $admin = $this->actingAsAdmin();

    $response = $this->postJson('/api/events', [
        'name' => 'Test Event'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_time', 'end_time', 'max_participants']);
});

test('event creation fails with invalid dates', function () {
    $admin = $this->actingAsAdmin();
    $category = EventCategory::factory()->create();

    $response = $this->postJson('/api/events', [
        'name' => 'Test Event',
        'description' => 'Test Description',
        'start_time' => Carbon::yesterday()->format('Y-m-d H:i:s'), // Past date
        'end_time' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
        'max_participants' => 50,
        'category_id' => $category->id,
        'timezone' => 'UTC'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_time']);
});

test('organizer can update their own event', function () {
    $organizer = $this->actingAsOrganizer();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->putJson("/api/events/{$event->id}", [
        'name' => 'Updated Event Name'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'name' => 'Updated Event Name'
            ]
        ]);
});

test('organizer cannot update other organizers events', function () {
    $organizer = $this->actingAsOrganizer();
    $otherOrganizer = $this->createOrganizer();
    $event = Event::factory()->create(['organizer_id' => $otherOrganizer->id]);

    $response = $this->putJson("/api/events/{$event->id}", [
        'name' => 'Updated Event Name'
    ]);

    $response->assertStatus(403);
});

test('admin can update any event', function () {
    $admin = $this->actingAsAdmin();
    $organizer = $this->createOrganizer();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->putJson("/api/events/{$event->id}", [
        'name' => 'Updated By Admin'
    ]);

    $response->assertStatus(200);
});

test('admin can delete any event', function () {
    $admin = $this->actingAsAdmin();
    $event = Event::factory()->create();

    $response = $this->deleteJson("/api/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Event deleted successfully'
        ]);
});

test('organizer cannot delete events', function () {
    $organizer = $this->actingAsOrganizer();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $response = $this->deleteJson("/api/events/{$event->id}");

    $response->assertStatus(403);
});

test('authenticated user can view public events', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create(['is_private' => false]);

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => true
        ]);
});

test('regular user cannot view private events', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create(['is_private' => true]);

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertStatus(403);
});

test('admin can view private events', function () {
    $admin = $this->actingAsAdmin();
    $event = Event::factory()->create(['is_private' => true]);

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertStatus(200);
});

test('event list is paginated', function () {
    $user = $this->actingAsUser();
    Event::factory()->count(20)->create(['is_private' => false]);

    $response = $this->getJson('/api/events');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'data',
                'current_page',
                'per_page',
                'total'
            ]
        ]);
});
