<?php

use App\Filament\Candidate\Pages\Documents;
use App\Models\CandidateCandidateStatus;
use App\Models\CandidateStatus;
use App\Models\Company;
use App\Models\EducationCandidate;
use App\Models\Industry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function makeCandidateUser(string $statusName, array $candidateAttributes = []): User
{
    $company = Company::factory()->create();
    $industry = Industry::factory()->create(['slug' => 'education']);
    $candidate = EducationCandidate::factory()->create(array_merge([
        'company_id' => $company->id,
    ], $candidateAttributes));

    $status = CandidateStatus::factory()->create([
        'company_id' => $company->id,
        'industry_id' => $industry->id,
        'name' => $statusName,
    ]);

    CandidateCandidateStatus::create([
        'model_type' => EducationCandidate::class,
        'model_id' => $candidate->id,
        'candidate_status_id' => $status->id,
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'candidate_id' => $candidate->id,
        'candidate_type' => EducationCandidate::class,
    ]);
    $user->assignRole('candidate');
    $user->industries()->attach($industry);

    return $user;
}

/** @return array<string, mixed> */
function documentTypesFor(User $user): array
{
    test()->actingAs($user);

    return Livewire::test(Documents::class)->instance()->documentTypes();
}

test('a vetting candidate is redirected from the candidate panel home to documents', function () {
    $user = makeCandidateUser('Vetting');

    $this->actingAs($user)->get('/candidate')->assertRedirect('/candidate/documents');
});

test('a vetting candidate can access the documents page directly without a redirect loop', function () {
    $user = makeCandidateUser('Vetting');

    $this->actingAs($user)->get('/candidate/documents')->assertOk();
});

test('an onboarding candidate can access the documents page', function () {
    $user = makeCandidateUser('Onboarding');

    $this->actingAs($user)->get('/candidate/documents')->assertOk();
});

test('the documents page always lists the base document types', function () {
    $user = makeCandidateUser('Onboarding');

    $types = documentTypesFor($user);

    expect(array_keys($types))->toEqual([
        'cv',
        'photo',
        'prevent_training',
        'safeguarding_training',
        'proof_of_address',
    ]);
});

test('a passport row appears when right to work is passport', function () {
    $user = makeCandidateUser('Onboarding', ['right_to_work_type' => 'passport']);

    $types = documentTypesFor($user);

    expect($types)->toHaveKey('passport');
    expect($types)->not->toHaveKey('birth_certificate');
});

test('a birth certificate row appears when right to work is birth certificate', function () {
    $user = makeCandidateUser('Onboarding', ['right_to_work_type' => 'birth_certificate']);

    $types = documentTypesFor($user);

    expect($types)->toHaveKey('birth_certificate');
    expect($types)->not->toHaveKey('passport');
});

test('no right to work document row appears when right to work is visa', function () {
    $user = makeCandidateUser('Onboarding', ['right_to_work_type' => 'visa', 'visa_share_code' => 'ABC123XYZ']);

    $types = documentTypesFor($user);

    expect($types)->not->toHaveKey('birth_certificate');
    expect($types)->not->toHaveKey('passport');
});

test('a dbs row appears when the candidate has a dbs', function () {
    $user = makeCandidateUser('Onboarding', ['has_dbs' => 'yes']);

    $types = documentTypesFor($user);

    expect($types)->toHaveKey('dbs');
    expect($types)->not->toHaveKey('proof_of_address_2');
});

test('a second proof of address row appears when the candidate has no dbs', function () {
    $user = makeCandidateUser('Onboarding', ['has_dbs' => 'no']);

    $types = documentTypesFor($user);

    expect($types)->toHaveKey('proof_of_address_2');
    expect($types)->not->toHaveKey('dbs');
});

test('a uk naric row appears when the candidate opts in', function () {
    $user = makeCandidateUser('Onboarding', ['has_naric' => 'yes']);

    $types = documentTypesFor($user);

    expect($types)->toHaveKey('uk_naric');
});

test('no uk naric row appears by default', function () {
    $user = makeCandidateUser('Onboarding');

    $types = documentTypesFor($user);

    expect($types)->not->toHaveKey('uk_naric');
});

test('rows reflect existing candidate_documents records', function () {
    $user = makeCandidateUser('Onboarding');
    $user->candidate->documents()->createMany([
        ['document_type' => 'cv', 'path' => 'company/education/1/cv.pdf'],
        ['document_type' => 'photo', 'path' => 'company/education/1/photo.jpg'],
    ]);

    $types = documentTypesFor($user);

    expect($types['cv']['uploaded'])->toBeTrue();
    expect($types['cv']['path'])->toBe('company/education/1/cv.pdf');
    expect($types['photo']['uploaded'])->toBeTrue();
    expect($types['photo']['path'])->toBe('company/education/1/photo.jpg');
});

test('the prevent training info action is only visible on the prevent training row', function () {
    $user = makeCandidateUser('Onboarding');

    $this->actingAs($user);

    Livewire::test(Documents::class)
        ->assertActionVisible(TestAction::make('preventTrainingInfo')->table(record: 'prevent_training'))
        ->assertActionHidden(TestAction::make('preventTrainingInfo')->table(record: 'cv'));
});

test('uploading a document creates a candidate_documents record and stores the file', function () {
    $user = makeCandidateUser('Onboarding');
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    Livewire::test(Documents::class)
        ->callAction(
            TestAction::make('upload')->table(record: 'cv'),
            data: ['file' => $file],
        )
        ->assertHasNoActionErrors();

    $document = $user->candidate->fresh()->documents()->where('document_type', 'cv')->first();

    expect($document)->not->toBeNull();
    Storage::disk('local')->assertExists($document->path);
});

test('updating a document replaces the stored file and record', function () {
    $user = makeCandidateUser('Onboarding');
    $this->actingAs($user);

    $originalFile = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    Livewire::test(Documents::class)
        ->callAction(TestAction::make('upload')->table(record: 'cv'), data: ['file' => $originalFile]);

    $originalDocument = $user->candidate->fresh()->documents()->where('document_type', 'cv')->first();
    $originalPath = $originalDocument->path;

    $newFile = UploadedFile::fake()->create('cv-updated.pdf', 100, 'application/pdf');

    Livewire::test(Documents::class)
        ->callAction(
            TestAction::make('update')->table(record: 'cv'),
            data: ['file' => $newFile],
        )
        ->assertHasNoActionErrors();

    $candidate = $user->candidate->fresh();
    expect($candidate->documents()->where('document_type', 'cv')->count())->toBe(1);

    $updatedDocument = $candidate->documents()->where('document_type', 'cv')->first();
    expect($updatedDocument->id)->toBe($originalDocument->id);
    expect($updatedDocument->path)->not->toBe($originalPath);

    Storage::disk('local')->assertMissing($originalPath);
    Storage::disk('local')->assertExists($updatedDocument->path);
});

test('removing a document deletes the stored file and record', function () {
    $user = makeCandidateUser('Onboarding');
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    Livewire::test(Documents::class)
        ->callAction(TestAction::make('upload')->table(record: 'cv'), data: ['file' => $file]);

    $document = $user->candidate->fresh()->documents()->where('document_type', 'cv')->first();
    $path = $document->path;

    Livewire::test(Documents::class)
        ->callAction(TestAction::make('remove')->table(record: 'cv'));

    expect($user->candidate->fresh()->documents()->where('document_type', 'cv')->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($path);
});

test('uploaded documents are stored under the company and industry name, not their ids', function () {
    $user = makeCandidateUser('Onboarding');
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    Livewire::test(Documents::class)
        ->callAction(TestAction::make('upload')->table(record: 'cv'), data: ['file' => $file]);

    $candidate = $user->candidate->fresh();
    $document = $candidate->documents()->where('document_type', 'cv')->first();

    $expectedCompanySlug = Str::slug($candidate->company->name);
    $expectedIndustrySlug = Str::slug(Industry::where('slug', 'education')->value('name'));

    expect($document->path)->toStartWith("{$expectedCompanySlug}/{$expectedIndustrySlug}/{$candidate->id}/");
});
