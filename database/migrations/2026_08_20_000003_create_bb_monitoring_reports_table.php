<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bb_monitoring_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->cascadeOnDelete();
            $table->foreignId('bb_user_id')->constrained('users')->cascadeOnDelete();

            // Inspection Details
            $table->date('inspection_date')->nullable();
            $table->text('site_description')->nullable();

            // BB's Observed Progress
            $table->decimal('bb_physical_progress_pct', 5, 2)->default(0);
            $table->text('bb_physical_progress_description')->nullable();
            $table->decimal('bb_financial_progress_pct', 5, 2)->default(0);
            $table->text('bb_financial_progress_description')->nullable();

            // Official BB Report Document
            $table->string('bb_report_doc_path')->nullable();

            // Geo-Tagged Field Evidence
            // Each entry: { path, type (photo|video), lat, lng, caption }
            $table->json('geo_tagged_files')->nullable();

            // Status: DRAFT → SUBMITTED
            $table->string('status')->default('DRAFT');
            $table->text('remarks')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bb_monitoring_reports');
    }
};
