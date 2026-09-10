<?php

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
        Schema::create('payment_request_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('payment_requests')->onDelete('cascade');
            $table->foreignId('scheme_id')->nullable()->constrained('schemes')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('version_label')->default('v1.0');
            $table->string('status_at_revision')->nullable();
            
            // Financial request data
            $table->decimal('requested_amount_cr', 15, 2)->nullable();
            $table->integer('instalment_number')->nullable();
            $table->text('bank_details')->nullable();
            $table->text('state_remarks')->nullable();

            // Progress metrics
            $table->decimal('physical_progress_pct', 5, 2)->nullable();
            $table->text('physical_progress_description')->nullable();
            $table->decimal('financial_progress_pct', 5, 2)->nullable();
            $table->text('financial_progress_description')->nullable();
            $table->text('narrative_progress_report')->nullable();

            // Review notes / Decisions
            $table->string('bb_decision')->nullable();
            $table->text('bb_remarks')->nullable();
            $table->string('mojs_decision')->nullable();
            $table->text('mojs_remarks')->nullable();

            // Historical Document Paths
            $table->string('utilization_certificate_path')->nullable();
            $table->json('voucher_doc_paths')->nullable();
            $table->string('progress_report_doc_path')->nullable();
            $table->string('state_govt_doc_path')->nullable();
            $table->json('additional_doc_paths')->nullable();

            $table->string('author_name')->nullable();
            $table->string('author_role')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_request_revisions');
    }
};
