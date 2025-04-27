<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->admin()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123')
        ]);

        // Create organizer
        $organizer = User::factory()->organizer()->create([
            'email' => 'organizer@test.com',
            'password' => bcrypt('password123')
        ]);

        // Create regular user
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => bcrypt('password123')
        ]);

        // Create categories
        $categories = EventCategory::factory()->count(3)->create();

        // Create various event types
        $upcomingEvent = Event::factory()->create([
            'category_id' => $categories->random()->id,
            'organizer_id' => $organizer->id,
            'start_time' => Carbon::tomorrow(),
            'end_time' => Carbon::tomorrow()->addHours(2)
        ]);

        $ongoingEvent = Event::factory()->ongoing()->create([
            'category_id' => $categories->random()->id,
            'organizer_id' => $organizer->id
        ]);

        $completedEvent = Event::factory()->completed()->create([
            'category_id' => $categories->random()->id,
            'organizer_id' => $organizer->id
        ]);

        $privateEvent = Event::factory()->private()->create([
            'category_id' => $categories->random()->id,
            'organizer_id' => $organizer->id
        ]);

        // Create some registrations
        EventRegistration::factory()->create([
            'event_id' => $upcomingEvent->id,
            'user_id' => $user->id
        ]);

        EventRegistration::factory()->cancelled()->create([
            'event_id' => $completedEvent->id,
            'user_id' => $user->id
        ]);
    }
}
