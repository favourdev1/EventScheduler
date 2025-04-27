<?php

use App\Models\EventCategory;

test('authenticated users can view categories', function () {
    $user = $this->actingAsUser();
    EventCategory::factory()->count(5)->create();

    $response = $this->getJson('/api/categories');

    Log::info('Response:', $response->json());
    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'events_count'
                ]
            ]
        ]);
});

test('admin can create category', function () {
    $admin = $this->actingAsAdmin();

    $response = $this->postJson('/api/categories', [
        'name' => 'Test Category',
        'description' => 'Test Description'
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'Category created successfully'
        ]);
});

test('non-admin cannot create category', function () {
    $user = $this->actingAsUser();

    $response = $this->postJson('/api/categories', [
        'name' => 'Test Category',
        'description' => 'Test Description'
    ]);

    $response->assertStatus(403);
});

test('admin can update category', function () {
    $admin = $this->actingAsAdmin();
    $category = EventCategory::factory()->create();

    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => 'Updated Category',
        'description' => 'Updated Description'
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'name' => 'Updated Category',
                'description' => 'Updated Description'
            ]
        ]);
});

test('admin cannot create duplicate category name', function () {
    $admin = $this->actingAsAdmin();
    $existingCategory = EventCategory::factory()->create();

    $response = $this->postJson('/api/categories', [
        'name' => $existingCategory->name
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('admin can delete category', function () {
    $admin = $this->actingAsAdmin();
    $category = EventCategory::factory()->create();

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Category deleted successfully'
        ]);
});

test('non-admin cannot delete category', function () {
    $user = $this->actingAsUser();
    $category = EventCategory::factory()->create();

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertStatus(403);
});
