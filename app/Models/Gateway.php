<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $table = 'meter_rtu';

    protected $primaryKey = 'rtu_id';

    protected $fillable = [
        'site_idx',
        'location_idx',
        'site_code',
        'gateway_sn',
        'gateway_mac',
        'gateway_ip',
        'connection_type',
        'ip_netmask',
        'ip_gateway',
        'rtu_server_ip',
        'gateway_description',
        'idf_number',
        'switch_name',
        'idf_port',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
