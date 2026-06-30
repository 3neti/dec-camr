<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'meter_division_table';

    protected $primaryKey = 'division_id';

    protected $fillable = [
        'division_code',
        'division_name',
        'created_by_user_idx',
        'modified_by_user_idx',
    ];
}
