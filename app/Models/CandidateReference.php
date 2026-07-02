<?php

namespace App\Models;

use App\Enums\ReferenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CandidateReference extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => ReferenceType::class,
        'worked_from' => 'date',
        'worked_to' => 'date',
        'consent_to_contact' => 'boolean',
    ];

    public function candidate(): MorphTo
    {
        return $this->morphTo();
    }
}
