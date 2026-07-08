<?php

use App\Enums\DocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('candidate');
            $table->enum('document_type', array_column(DocumentType::cases(), 'value'));
            $table->string('path');
            $table->timestamps();

            $table->unique(['candidate_type', 'candidate_id', 'document_type'], 'candidate_documents_candidate_document_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_documents');
    }
};
