<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'campus_code', 'campus_name', 'campus_type', 'parent_campus_id',
        'county', 'sub_county', 'physical_address', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parentCampus()
    {
        return $this->belongsTo(self::class, 'parent_campus_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
