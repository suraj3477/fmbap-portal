<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('schemes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state')->nullable();

            // Workflow Status
            // DRAFT → SUBMITTED_TO_BB → BB_MONITORING_PENDING → FORWARDED_TO_MOJS → APPROVED / REJECTED / NEEDS_CORRECTION
            $table->string('status')->default('DRAFT');

            // Step 2: Financial Details
            $table->decimal('requested_amount_cr', 12, 2)->default(0);
            $table->integer('instalment_number')->nullable();
            $table->text('bank_details')->nullable();
            $table->text('state_remarks')->nullable();

            // Step 3: Progress (State-reported)
            $table->decimal('physical_progress_pct', 5, 2)->default(0);
            $table->text('physical_progress_description')->nullable();
            $table->decimal('financial_progress_pct', 5, 2)->default(0);
            $table->text('financial_progress_description')->nullable();
            $table->text('narrative_progress_report')->nullable();

            // Step 4: Documents
            $table->string('utilization_certificate_path')->nullable();
            $table->json('voucher_doc_paths')->nullable();        // multiple vouchers
            $table->string('progress_report_doc_path')->nullable();
            $table->json('additional_doc_paths')->nullable();     // extra attachments

            // BB Decision
            $table->string('bb_decision')->default('PENDING'); // PENDING, APPROVED, NEEDS_CORRECTION, REJECTED, FORWARDED_TO_MOJS
            $table->text('bb_remarks')->nullable();

            // MoJS Decision
            $table->string('mojs_decision')->default('PENDING'); // PENDING, APPROVED, NEEDS_CORRECTION, REJECTED
            $table->text('mojs_remarks')->nullable();

            // Timestamps for key workflow events
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('forwarded_to_mojs_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
