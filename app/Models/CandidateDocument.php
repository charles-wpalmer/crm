<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CandidateDocument extends Model
{
    protected $guarded = [];

    protected $casts = [
        'document_type' => DocumentType::class,
    ];

    public function candidate(): MorphTo
    {
        return $this->morphTo();
    }
}
