<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meter extends Model
{
    use HasFactory;

    protected $table = 'meter_details';

    protected $primaryKey = 'meter_id';

    protected $fillable = [
        'site_idx',
        'site_code',
        'rtu_idx',
        'location_idx',
        'building_idx',
        'config_idx',
        'meter_name',
        'meter_name_addressable',
        'meter_load_profile',
        'meter_default_name',
        'meter_type',
        'meter_brand',
        'meter_role',
        'meter_remarks',
        'customer_name',
        'meter_multiplier',
        'meter_status',
        'last_log_update',
        'soft_rev',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
