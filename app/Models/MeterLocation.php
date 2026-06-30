<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterLocation extends Model
{
    use HasFactory;

    protected $table = 'meter_location_table';

    protected $primaryKey = 'location_id';

    protected $fillable = [
        'site_idx',
        'building_id',
        'location_code',
        'location_description',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
