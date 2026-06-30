<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurationFile extends Model
{
    use HasFactory;

    protected $table = 'meter_configuration_file';

    protected $primaryKey = 'config_id';

    protected $fillable = [
        'meter_model',
        'config_file',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
