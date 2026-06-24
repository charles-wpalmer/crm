<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Database\Factories\CandidateSkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateSkill extends Model
{
    /** @use HasFactory<CandidateSkillFactory> */
    use BelongsToCompany;

    use HasFactory;

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CandidateSkill::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CandidateSkill::class, 'parent_id')->withoutGlobalScopes();
    }

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(EducationCandidate::class, 'education_candidate_skills');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
