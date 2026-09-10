<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fmbap_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fmbap_project_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->enum('document_type', ['DPR_PDF', 'UTILIZATION_CERTIFICATE', 'INSPECTION_PHOTO', 'SITE_MAP']);
            $table->string('uploaded_by')->default('Brahmaputra Board Inspector');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fmbap_documents');
    }
};