<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Tests\TestCase::class)
    ->beforeEach(function () {
        // Run migrations for each test
        $this->artisan('migrate:fresh');
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeValidResponse', function () {
    return $this->toBeObject()
        ->toHaveProperty('status')
        ->toHaveProperty('message');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createTestEvent(array $attributes = [])
{
    return \App\Models\Event::factory()->create($attributes);
}

function createTestUser(string $role = 'user', array $attributes = [])
{
    return \App\Models\User::factory()
        ->state(['role' => $role])
        ->create($attributes);
}
