<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $table = 'meter_site';

    protected $primaryKey = 'site_id';

    protected $fillable = [
        'division_idx',
        'company_idx',
        'building_idx',
        'site_code',
        'building_description',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
