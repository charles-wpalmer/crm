<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationApplication extends Model
{
    protected $casts = [
        'expires_on' => 'date',
        'completed_at' => 'datetime',
        'email_verified_at' => 'boolean',
    ];
}
