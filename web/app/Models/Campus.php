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

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'main' => 'Main',
            'campus' => 'Campus',
            'community_college' => 'Community College',
        ];
    }

  /** @return list<string> */
    public static function typeKeys(): array
    {
        return array_keys(self::typeOptions());
    }

    public static function typeLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return '-';
        }

        return self::typeOptions()[$type] ?? ucwords(str_replace('_', ' ', $type));
    }
}
