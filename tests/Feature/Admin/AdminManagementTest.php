<?php

use App\Models\User;
use App\Models\Event;
use App\Models\EventRegistration;
use Carbon\Carbon;

test('admin can list all users', function () {
    $admin = $this->actingAsAdmin();
    User::factory()->count(5)->create();

    $response = $this->getJson('/api/admin/users');

    Log::info('Response:', $response->json());
    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_active',
                        'organized_events_count',
                        'registrations_count'
                    ]
                ],
                'current_page',
                'per_page'
            ]
        ]);
});

test('non-admin cannot list users', function () {
    $user = $this->actingAsUser();

    $response = $this->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('admin can create new user', function () {
    $admin = $this->actingAsAdmin();

    $response = $this->postJson('/api/admin/users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'role' => 'organizer',
        'is_active' => true
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'User created successfully'
        ]);
});

test('admin can update user', function () {
    $admin = $this->actingAsAdmin();
    $user = User::factory()->create();

    $response = $this->putJson("/api/admin/users/{$user->id}", [
        'name' => 'Updated Name',
        'role' => 'organizer'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'name' => 'Updated Name',
                'role' => 'organizer'
            ]
        ]);
});

test('admin cannot delete last admin user', function () {
    $admin = $this->actingAsAdmin();

    $response = $this->deleteJson("/api/admin/users/{$admin->id}");

    $response->assertStatus(400)
        ->assertJson([
            'status' => false,
            'message' => 'Cannot delete the last admin user'
        ]);
});

test('admin can delete non-admin user', function () {
    $admin = $this->actingAsAdmin();
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->deleteJson("/api/admin/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
});

test('admin can toggle user active status', function () {
    $admin = $this->actingAsAdmin();
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->postJson("/api/admin/users/{$user->id}/toggle-active");

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'is_active' => false
            ]
        ]);
});

test('admin can force register user to event', function () {
    $admin = $this->actingAsAdmin();
    $user = User::factory()->create();
    $event = Event::factory()->create([
        'max_participants' => 1,
        'status' => 'upcoming'
    ]);

    // First fill up the event
    Event::factory()->create(['max_participants' => 1]);

    // Admin should still be able to force register
    $response = $this->postJson("/api/admin/events/{$event->id}/force-register", [
        'user_id' => $user->id
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'User registered successfully'
        ]);
});

test('admin can remove participant from event', function () {
    $admin = $this->actingAsAdmin();
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $registration = EventRegistration::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'registered'
    ]);

    $response = $this->deleteJson("/api/admin/events/{$event->id}/remove-participant", [
        'user_id' => $user->id,
        'reason' => 'Administrative action'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Participant removed successfully'
        ]);
});

test('admin can view system statistics', function () {
    $admin = $this->actingAsAdmin();

    // Create some test data
    User::factory()->count(3)->create();
    Event::factory()->count(5)->create();
    EventRegistration::factory()->count(10)->create();

    $response = $this->getJson('/api/admin/statistics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'users' => [
                    'total',
                    'active',
                    'by_role'
                ],
                'events' => [
                    'total',
                    'upcoming',
                    'ongoing',
                    'this_month',
                    'this_year'
                ],
                'registrations' => [
                    'total',
                    'active',
                    'cancelled',
                    'this_month'
                ]
            ]
        ]);
});
