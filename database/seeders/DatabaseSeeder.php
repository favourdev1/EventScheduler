<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin and test users
        $this->call([
            AdminSeeder::class,
            EventCategorySeeder::class,
        ]);

        // Create a test organizer
        $organizer = User::create([
            'name' => 'Test Organizer',
            'email' => 'organizer@example.com',
            'password' => bcrypt('password123'),
            'role' => 'organizer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create a regular test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create some sample events
        $now = Carbon::now();
        
        // Upcoming events
        for ($i = 1; $i <= 3; $i++) {
            Event::create([
                'name' => "Upcoming Event $i",
                'description' => "This is an upcoming test event $i",
                'start_time' => $now->copy()->addDays($i)->setHour(10),
                'end_time' => $now->copy()->addDays($i)->setHour(12),
                'max_participants' => 50,
                'status' => 'upcoming',
                'is_private' => false,
                'category_id' => rand(1, 5),
                'organizer_id' => $organizer->id,
                'timezone' => 'UTC'
            ]);
        }

        // Ongoing event
        Event::create([
            'name' => "Ongoing Event",
            'description' => "This is an ongoing test event",
            'start_time' => $now->copy()->subHour(),
            'end_time' => $now->copy()->addHours(2),
            'max_participants' => 30,
            'status' => 'ongoing',
            'is_private' => false,
            'category_id' => rand(1, 5),
            'organizer_id' => $organizer->id,
            'timezone' => 'UTC'
        ]);

        // Completed event
        Event::create([
            'name' => "Completed Event",
            'description' => "This is a completed test event",
            'start_time' => $now->copy()->subDays(1)->setHour(10),
            'end_time' => $now->copy()->subDays(1)->setHour(12),
            'max_participants' => 40,
            'status' => 'completed',
            'is_private' => false,
            'category_id' => rand(1, 5),
            'organizer_id' => $organizer->id,
            'timezone' => 'UTC'
        ]);

        // Private event
        Event::create([
            'name' => "Private Event",
            'description' => "This is a private test event",
            'start_time' => $now->copy()->addDays(5)->setHour(14),
            'end_time' => $now->copy()->addDays(5)->setHour(16),
            'max_participants' => 20,
            'status' => 'upcoming',
            'is_private' => true,
            'category_id' => rand(1, 5),
            'organizer_id' => $organizer->id,
            'timezone' => 'UTC'
        ]);
    }
}
