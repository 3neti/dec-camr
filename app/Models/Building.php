<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $table = 'meter_building_table';

    protected $primaryKey = 'building_id';

    protected $fillable = [
        'site_idx',
        'building_code',
        'building_description',
        'cut_off',
        'device_ip_range',
        'ip_network',
        'ip_netmask',
        'ip_gateway',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
