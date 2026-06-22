<?php

namespace Database\Factories;

use App\Models\ProgressPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressPhoto>
 */
class ProgressPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'      => \App\Models\User::factory(),
            'taken_at'     => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'storage_path' => 'progress-photos/' . \Illuminate\Support\Str::uuid() . '.jpg',
            'caption'      => fake()->optional()->sentence(),
        ];
    }
}
