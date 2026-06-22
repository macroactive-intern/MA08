<?php

namespace Database\Factories;

use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Measurement>
 */
class MeasurementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'             => \App\Models\User::factory(),
            'measured_at'         => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'weight'              => fake()->randomFloat(2, 50, 150),
            'body_fat_percentage' => fake()->randomFloat(1, 5, 40),
            'notes'               => fake()->optional()->sentence(),
            'unit_system'         => fake()->randomElement(['metric', 'imperial']),
        ];
    }
}
