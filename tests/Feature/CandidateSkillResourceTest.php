<?php

use App\Filament\Resources\CandidateSkills\Pages\EditCandidateSkill;
use App\Filament\Resources\CandidateSkills\Pages\ListCandidateSkills;
use App\Models\CandidateSkill;
use App\Models\Industry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->industry = Industry::factory()->create();
    Cache::put("user.{$this->user->id}.active_industry", $this->industry->slug);
    Cache::put("user.{$this->user->id}.active_industry_id", $this->industry->id);
});

test('list page renders', function () {
    Livewire::test(ListCandidateSkills::class)
        ->assertSuccessful();
});

test('can create a top-level skill', function () {
    Livewire::test(ListCandidateSkills::class)
        ->callAction('create', data: [
            'name' => 'Classroom Management',
            'sector' => null,
            'parent_id' => null,
        ])
        ->assertHasNoActionErrors();

    expect(CandidateSkill::where('name', 'Classroom Management')->exists())->toBeTrue();
});

test('can create a child skill', function () {
    $parent = CandidateSkill::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Teaching',
        'parent_id' => null,
    ]);

    Livewire::test(ListCandidateSkills::class)
        ->callAction('create', data: [
            'name' => 'Primary Teaching',
            'sector' => null,
            'parent_id' => $parent->id,
        ])
        ->assertHasNoActionErrors();

    expect(CandidateSkill::where('name', 'Primary Teaching')->where('parent_id', $parent->id)->exists())->toBeTrue();
});

test('edit page renders', function () {
    $skill = CandidateSkill::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
    ]);

    Livewire::test(EditCandidateSkill::class, ['record' => $skill->getRouteKey()])
        ->assertSuccessful();
});

test('skill name can be updated', function () {
    $skill = CandidateSkill::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Old Name',
    ]);

    Livewire::test(EditCandidateSkill::class, ['record' => $skill->getRouteKey()])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($skill->refresh()->name)->toBe('New Name');
});
