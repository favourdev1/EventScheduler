<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use App\Mail\NewUserRegisteredAdminNotification;

beforeEach(function () {
    Mail::fake();
});

test('user can register with valid data', function () {
    // Create an admin user first
    User::factory()->create([
        'role' => 'admin'
    ]);

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user'
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role'
                ],
                'token'
            ]
        ]);

    Mail::assertQueued(WelcomeEmail::class);
    Mail::assertQueued(NewUserRegisteredAdminNotification::class);
});

test('registration fails with missing fields', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registration fails with duplicate email', function () {
    $existingUser = User::factory()->create();

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => $existingUser->email,
        'password' => 'password123',
        'password_confirmation' => 'password123'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123'
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user',
                'token'
            ]
        ]);
});

test('login fails with incorrect password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123')
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrongpassword'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'status' => false
        ]);
});

test('login fails with unregistered email', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123'
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'status' => false
        ]);
});

test('user can logout when authenticated', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Successfully logged out'
        ]);
});

test('logout fails without authentication', function () {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'role'
            ]
        ]);
});

test('unauthenticated user cannot access profile', function () {
    $response = $this->getJson('/api/me');

    $response->assertStatus(401);
});
