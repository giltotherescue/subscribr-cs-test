<?php

namespace Database\Factories;

use App\Models\Attempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Attempt::generateToken(),
            'assessment_version' => config('assessment.version', '2025-12-11'),
            'candidate_name' => fake()->name(),
            'candidate_email' => fake()->unique()->safeEmail(),
            'status' => 'in_progress',
            'started_at' => now(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'completed_at' => now(),
            'duration_seconds' => fake()->numberBetween(1800, 5400),
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewed_at' => now(),
        ]);
    }
}
