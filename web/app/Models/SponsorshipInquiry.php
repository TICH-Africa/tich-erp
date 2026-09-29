<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorshipInquiry extends Model
{
    protected $table = 'sponsorship_inquiries';

    protected $fillable = [
        'sponsor_type',
        'duration',
        'preferred_field',
        'sponsor_name',
        'sponsor_email',
        'sponsor_phone',
        'sponsor_message',
        'status',
    ];
}