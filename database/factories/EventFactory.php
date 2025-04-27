<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startTime = Carbon::now()->addDays(rand(1, 30));

        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addHours(2),
            'max_participants' => fake()->numberBetween(10, 100),
            'status' => 'upcoming',
            'is_private' => fake()->boolean(20),
            'category_id' => EventCategory::factory(),
            'organizer_id' => User::factory()->organizer(),
            'timezone' => 'UTC'
        ];
    }

    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            $startTime = Carbon::now()->subDays(rand(1, 30));
            return [
                'start_time' => $startTime,
                'end_time' => $startTime->copy()->addHours(2),
                'status' => 'completed'
            ];
        });
    }

    public function ongoing(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'start_time' => Carbon::now()->subHour(),
                'end_time' => Carbon::now()->addHours(2),
                'status' => 'ongoing'
            ];
        });
    }

    public function private(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_private' => true
            ];
        });
    }
}
