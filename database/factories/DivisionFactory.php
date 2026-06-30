<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'division_code' => fake()->bothify('DIV-###'),
            'division_name' => fake()->unique()->words(2, true),
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
