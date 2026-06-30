<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'meter_company_table';

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'company_code',
        'company_name',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
