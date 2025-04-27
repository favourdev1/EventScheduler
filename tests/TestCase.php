<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function createOrganizer(): User
    {
        return User::factory()->create([
            'role' => 'organizer',
            'is_active' => true,
        ]);
    }

    protected function createUser(): User
    {
        return User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    protected function actingAsAdmin(): User
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);
        return $admin;
    }

    protected function actingAsOrganizer(): User
    {
        $organizer = $this->createOrganizer();
        Sanctum::actingAs($organizer);
        return $organizer;
    }

    protected function actingAsUser(): User
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);
        return $user;
    }
}
