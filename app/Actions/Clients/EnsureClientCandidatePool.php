<?php

namespace App\Actions\Clients;

use App\Models\CandidatePool;
use App\Models\Client;
use Lorisleiva\Actions\Concerns\AsAction;

class EnsureClientCandidatePool
{
    use AsAction;

    /**
     * Every client gets their own candidate pool, created the moment they're
     * added — candidates they book go into it, so consultants can see who
     * this client already knows and likes.
     */
    public function handle(Client $client): CandidatePool
    {
        return CandidatePool::query()->firstOrCreate(
            ['client_id' => $client->id],
            [
                'company_id' => $client->company_id,
                'industry_id' => $client->industry_id,
                'user_id' => null,
                'company_pool' => true,
                'name' => "{$client->name} Candidates",
            ],
        );
    }
}
