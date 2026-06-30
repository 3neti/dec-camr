<?php

namespace Database\Factories;

use App\Models\ConfigurationFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConfigurationFile>
 */
class ConfigurationFileFactory extends Factory
{
    protected $model = ConfigurationFile::class;

    public function definition(): array
    {
        return [
            'meter_model' => 'N/A',
            'config_file' => fake()->unique()->lexify('cfg-??????.cfg'),
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
