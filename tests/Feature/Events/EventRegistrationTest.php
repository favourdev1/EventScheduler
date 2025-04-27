<?php

use App\Models\Event;
use App\Mail\EventRegistrationConfirmation;
use App\Mail\NewParticipantRegistered;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('user can register for an upcoming event', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create([
        'start_time' => Carbon::tomorrow(),
        'end_time' => Carbon::tomorrow()->addHours(2),
        'max_participants' => 10,
        'status' => 'upcoming'
    ]);

    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'Successfully registered for the event'
        ]);

    Mail::assertQueued(EventRegistrationConfirmation::class);
    Mail::assertQueued(NewParticipantRegistered::class);
});

test('user cannot register for a full event', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create([
        'start_time' => Carbon::tomorrow(),
        'end_time' => Carbon::tomorrow()->addHours(2),
        'max_participants' => 1,
        'status' => 'upcoming'
    ]);

    // Register first user to fill the event
    $otherUser = $this->createUser();
    $event->registrations()->create([
        'user_id' => $otherUser->id,
        'status' => 'registered'
    ]);

    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertStatus(422)
        ->assertJson([
            'status' => false
        ]);
});

test('user cannot register twice for the same event', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create([
        'start_time' => Carbon::tomorrow(),
        'end_time' => Carbon::tomorrow()->addHours(2),
        'status' => 'upcoming'
    ]);

    // First registration
    $this->postJson("/api/events/{$event->id}/register");

    // Second registration attempt
    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertStatus(422);
});

test('user cannot register for past events', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create([
        'start_time' => Carbon::yesterday(),
        'end_time' => Carbon::yesterday()->addHours(2),
        'status' => 'completed'
    ]);

    $response = $this->postJson("/api/events/{$event->id}/register");

    $response->assertStatus(403);
});

test('user cannot register for events with time conflicts', function () {
    $user = $this->actingAsUser();

    // Create and register for first event
    $event1 = Event::factory()->create([
        'start_time' => Carbon::tomorrow()->setHour(10),
        'end_time' => Carbon::tomorrow()->setHour(12),
        'status' => 'upcoming'
    ]);
    $this->postJson("/api/events/{$event1->id}/register");

    // Try to register for overlapping event
    $event2 = Event::factory()->create([
        'start_time' => Carbon::tomorrow()->setHour(11),
        'end_time' => Carbon::tomorrow()->setHour(13),
        'status' => 'upcoming'
    ]);

    $response = $this->postJson("/api/events/{$event2->id}/register");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['event']);
});

test('user can cancel their registration', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create([
        'start_time' => Carbon::tomorrow(),
        'status' => 'upcoming'
    ]);

    // Register first
    $this->postJson("/api/events/{$event->id}/register");

    // Then cancel
    $response = $this->postJson("/api/events/{$event->id}/cancel-registration", [
        'reason' => 'Schedule conflict'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Registration cancelled successfully'
        ]);
});

test('user cannot cancel non-existent registration', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create([
        'start_time' => Carbon::tomorrow(),
        'status' => 'upcoming'
    ]);

    $response = $this->postJson("/api/events/{$event->id}/cancel-registration");

    $response->assertStatus(404);
});

test('organizer can view event participants', function () {
    $organizer = $this->actingAsOrganizer();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $user = $this->createUser();
    $event->registrations()->create([
        'user_id' => $user->id,
        'status' => 'registered'
    ]);

    $response = $this->getJson("/api/events/{$event->id}/participants");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status'
                    ]
                ]
            ]
        ]);
});

test('non-organizer cannot view event participants', function () {
    $user = $this->actingAsUser();
    $event = Event::factory()->create();

    $response = $this->getJson("/api/events/{$event->id}/participants");

    $response->assertStatus(403);
});