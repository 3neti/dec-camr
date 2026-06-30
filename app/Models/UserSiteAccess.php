<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSiteAccess extends Model
{
    use HasFactory;

    protected $table = 'user_access_group';

    protected $primaryKey = 'user_access_id';

    protected $fillable = [
        'user_idx',
        'user_name',
        'user_expiration',
        'site_idx',
        'created_by_user_idx',
        'updated_by_user_idx',
        'access_list_src',
    ];
}
