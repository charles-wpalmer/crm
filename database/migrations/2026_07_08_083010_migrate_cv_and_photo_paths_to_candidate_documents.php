<?php

use App\Enums\DocumentType;
use App\Models\EducationApplication;
use App\Models\EducationCandidate;
use App\Services\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        EducationApplication::query()
            ->whereNotNull('cv_temp_path')
            ->with('educationCandidate')
            ->get()
            ->each(function (EducationApplication $application) {
                $candidate = $application->educationCandidate;

                if (! $candidate || ! Storage::disk('local')->exists($application->cv_temp_path)) {
                    return;
                }

                $path = Document::move($application->cv_temp_path, $candidate, 'cv');

                $candidate->documents()->updateOrCreate(
                    ['document_type' => DocumentType::Cv],
                    ['path' => $path],
                );
            });

        EducationCandidate::query()
            ->whereNotNull('photo_path')
            ->get()
            ->each(function (EducationCandidate $candidate) {
                if (! Storage::disk('local')->exists($candidate->photo_path)) {
                    return;
                }

                $path = Document::move($candidate->photo_path, $candidate, 'photo');

                $candidate->documents()->updateOrCreate(
                    ['document_type' => DocumentType::Photo],
                    ['path' => $path],
                );
            });

        Schema::table('education_applications', function (Blueprint $table) {
            $table->dropColumn('cv_temp_path');
        });

        Schema::table('education_candidates', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_applications', function (Blueprint $table) {
            $table->string('cv_temp_path')->nullable();
        });

        Schema::table('education_candidates', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
        });
    }
};
