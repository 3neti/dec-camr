<?php

namespace Database\Factories;

use App\Models\Gateway;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gateway>
 */
class GatewayFactory extends Factory
{
    protected $model = Gateway::class;

    public function definition(): array
    {
        return [
            'site_idx' => Site::factory(),
            'location_idx' => MeterLocation::factory(),
            'site_code' => 'SITE'.fake()->numberBetween(100, 999),
            'gateway_sn' => strtoupper(fake()->bothify('GW-###')),
            'gateway_mac' => fake()->macAddress(),
            'gateway_ip' => fake()->ipv4(),
            'connection_type' => 'LAN',
            'ip_netmask' => null,
            'ip_gateway' => null,
            'rtu_server_ip' => null,
            'gateway_description' => fake()->words(3, true),
            'update_rtu' => 0,
            'update_rtu_location' => 1,
            'update_rtu_ssh' => 0,
            'update_rtu_force_lp' => 0,
            'idf_number' => null,
            'switch_name' => null,
            'idf_port' => null,
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'last_log_update' => '0000-00-00 00:00:00',
            'soft_rev' => 0,
        ];
    }
}
