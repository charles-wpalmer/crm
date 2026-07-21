<?php

use App\Filament\Resources\CandidateStatuses\Pages\ManageCandidateStatusAutomations;
use App\Models\CandidateStatus;
use App\Models\CandidateStatusAutomation;
use App\Models\HealthcareCandidate;
use App\Models\Industry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    $this->industry = Industry::factory()->create(['slug' => 'education']);
    Cache::put("user.{$this->user->id}.active_industry", $this->industry->slug);
    Cache::put("user.{$this->user->id}.active_industry_id", $this->industry->id);
});

test('can create an automation with a relation column field from the suggestion list', function () {
    $onboarding = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Onboarding',
    ]);

    $vetting = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Vetting',
    ]);

    Livewire::test(ManageCandidateStatusAutomations::class)
        ->callAction('create', data: [
            'candidate_status_id' => $onboarding->id,
            'to_candidate_status_id' => $vetting->id,
            'completed_fields' => ['application.completed_at'],
        ])
        ->assertHasNoActionErrors();

    $automation = CandidateStatusAutomation::where('candidate_status_id', $onboarding->id)->first();

    expect($automation)->not->toBeNull();
    expect($automation->completed_fields)->toBe(['application.completed_at']);
});

test('can create an automation with a healthcare relation column field from the suggestion list', function () {
    $healthcareIndustry = Industry::factory()->create(['slug' => 'healthcare']);
    Cache::put("user.{$this->user->id}.active_industry", $healthcareIndustry->slug);
    Cache::put("user.{$this->user->id}.active_industry_id", $healthcareIndustry->id);

    $onboarding = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $healthcareIndustry->id,
        'name' => 'Onboarding',
    ]);

    $vetting = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $healthcareIndustry->id,
        'name' => 'Vetting',
    ]);

    Livewire::test(ManageCandidateStatusAutomations::class)
        ->callAction('create', data: [
            'candidate_status_id' => $onboarding->id,
            'to_candidate_status_id' => $vetting->id,
            'completed_fields' => ['application.email_verified'],
        ])
        ->assertHasNoActionErrors();

    $automation = CandidateStatusAutomation::where('candidate_status_id', $onboarding->id)->first();

    expect($automation)->not->toBeNull();
    expect($automation->completed_fields)->toBe(['application.email_verified']);
});

test('healthcare candidate field suggestions include own columns, application fields and to-many relations', function () {
    $suggestions = HealthcareCandidate::candidateFieldSuggestions();

    expect($suggestions)->toContain('first_name')
        ->toContain('email')
        ->toContain('application.email_verified')
        ->toContain('application.status')
        ->toContain('skills.*')
        ->not->toContain('id')
        ->not->toContain('company_id')
        ->not->toContain('application.candidate_id')
        ->not->toContain('application.candidate_type');
});

test('cannot create an automation with a field that is not in the suggestion list', function () {
    $onboarding = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Onboarding',
    ]);

    $vetting = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Vetting',
    ]);

    Livewire::test(ManageCandidateStatusAutomations::class)
        ->callAction('create', data: [
            'candidate_status_id' => $onboarding->id,
            'to_candidate_status_id' => $vetting->id,
            'completed_fields' => ['made_up_field_that_does_not_exist'],
        ])
        ->assertHasActionErrors(['completed_fields.0']);
});
