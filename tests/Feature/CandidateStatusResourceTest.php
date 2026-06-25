<?php

use App\Filament\Resources\CandidateStatuses\Pages\EditCandidateStatus;
use App\Filament\Resources\CandidateStatuses\Pages\ListCandidateStatuses;
use App\Models\CandidateStatus;
use App\Models\Industry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    $this->industry = Industry::factory()->create();
    Cache::put("user.{$this->user->id}.active_industry", $this->industry->slug);
    Cache::put("user.{$this->user->id}.active_industry_id", $this->industry->id);
});

test('list page renders', function () {
    Livewire::test(ListCandidateStatuses::class)
        ->assertSuccessful();
});

test('can create a status', function () {
    Livewire::test(ListCandidateStatuses::class)
        ->callAction('create', data: ['name' => 'Application Sent'])
        ->assertHasNoActionErrors();

    expect(CandidateStatus::where('name', 'Application Sent')->exists())->toBeTrue();
});

test('edit page renders', function () {
    $status = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
    ]);

    Livewire::test(EditCandidateStatus::class, ['record' => $status->getRouteKey()])
        ->assertSuccessful();
});

test('status name can be updated', function () {
    $status = CandidateStatus::factory()->create([
        'company_id' => $this->user->company_id,
        'industry_id' => $this->industry->id,
        'name' => 'Old Name',
    ]);

    Livewire::test(EditCandidateStatus::class, ['record' => $status->getRouteKey()])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($status->refresh()->name)->toBe('New Name');
});
