<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CandidatePool extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function candidates(): MorphToMany
    {
        $modelClass = Industry::candidateModelForSlug(active_industry() ?? '');

        if ($modelClass === null) {
            return $this->morphedByMany(EducationCandidate::class, 'candidate', 'candidate_pool_candidates')
                ->whereRaw('0 = 1');
        }

        return $this->morphedByMany($modelClass, 'candidate', 'candidate_pool_candidates');
    }

    /**
     * Same pivot as candidates(), but for an explicit candidate model class
     * rather than one resolved from the current user's active industry —
     * for use outside a live admin session (queued jobs, client-portal
     * requests) where that ambient state isn't a reliable source of truth.
     *
     * @param  class-string<Model>  $candidateModelClass
     */
    public function candidatesOfType(string $candidateModelClass): MorphToMany
    {
        return $this->morphedByMany($candidateModelClass, 'candidate', 'candidate_pool_candidates');
    }
}
