<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 150)->nullable();
            $table->string('extension', 10);
            $table->unsignedBigInteger('file_size');
            $table->string('document_type')->nullable();
            $table->string('status', 30)->index();
            $table->longText('raw_text')->nullable();
            $table->json('extracted_data')->nullable();
            $table->json('reviewed_data')->nullable();
            $table->string('ai_model')->nullable();
            $table->string('ai_provider')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('organization_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_imports');
    }
};
