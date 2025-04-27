<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Conference',
                'description' => 'Professional conferences and seminars'
            ],
            [
                'name' => 'Workshop',
                'description' => 'Hands-on learning sessions'
            ],
            [
                'name' => 'Webinar',
                'description' => 'Online educational events'
            ],
            [
                'name' => 'Networking',
                'description' => 'Professional networking events'
            ],
            [
                'name' => 'Training',
                'description' => 'Skill development sessions'
            ]
        ];

        foreach ($categories as $category) {
            EventCategory::create($category);
        }
    }
}