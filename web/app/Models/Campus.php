<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'campus_code', 'campus_name', 'campus_type', 'parent_campus_id', 'is_active',
    ];
}
