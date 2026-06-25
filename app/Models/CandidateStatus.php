<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Database\Factories\CandidateStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateStatus extends Model
{
    /** @use HasFactory<CandidateStatusFactory> */
    use BelongsToCompany;

    use HasFactory;

    protected $guarded = [];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CandidateCandidateStatus::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(CandidateStatusAutomation::class);
    }
}
