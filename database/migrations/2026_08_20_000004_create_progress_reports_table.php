<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('schemes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state')->nullable();

            // Reporting Period
            $table->string('reporting_period')->nullable(); // e.g. "Q1 2026-27"

            // Physical Progress
            $table->decimal('physical_progress_pct', 5, 2)->default(0);
            $table->text('physical_progress_description')->nullable();

            // Financial Progress
            $table->decimal('financial_progress_pct', 5, 2)->default(0);
            $table->text('financial_progress_description')->nullable();

            // Narrative
            $table->text('narrative_report')->nullable();

            // Document
            $table->string('progress_report_doc_path')->nullable();

            // Status: SUBMITTED → REVIEWED_BY_BB → REVIEWED_BY_MOJS / NEEDS_CORRECTION
            $table->string('status')->default('SUBMITTED');

            // Review Remarks
            $table->text('bb_remarks')->nullable();
            $table->timestamp('bb_reviewed_at')->nullable();
            $table->text('mojs_remarks')->nullable();
            $table->timestamp('mojs_reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_reports');
    }
};
