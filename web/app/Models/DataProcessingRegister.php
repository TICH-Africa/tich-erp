<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataProcessingRegister extends Model
{
    protected $table = 'data_processing_register';

    protected $fillable = [
        'activity_key',
        'name',
        'description',
        'data_subjects',
        'data_categories',
        'database_tables',
        'lawful_basis',
        'purposes',
        'retention',
        'recipients',
        'cross_border',
        'security_measures',
        'is_active',
    ];

    protected $casts = [
        'data_subjects' => 'array',
        'data_categories' => 'array',
        'database_tables' => 'array',
        'recipients' => 'array',
        'cross_border' => 'boolean',
        'is_active' => 'boolean',
    ];
}
