<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fmbap_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state')->nullable();
            $table->string('status')->default('SUBMITTED_BY_STATE');

            // Scheme Core Details
            $table->string('scheme_code')->nullable();
            $table->text('scheme_name')->nullable();
            $table->decimal('estimated_cost_cr', 12, 2)->default(0);
            $table->decimal('executed_amount_cr', 12, 2)->default(0);
            $table->string('funding_pattern')->default('90/10');

            // Shares
            $table->decimal('central_share_cr', 12, 2)->default(0);
            $table->decimal('state_share_cr', 12, 2)->default(0);

            // Released Funds
            $table->decimal('released_central_share_cr', 12, 2)->default(0);
            $table->decimal('released_state_share_cr', 12, 2)->default(0);

            // Calculated Balances
            $table->decimal('balance_central_share_cr', 12, 2)->default(0);
            $table->decimal('balance_state_share_cr', 12, 2)->default(0);

            // Remarks
            $table->text('remarks')->nullable();
            $table->text('bb_remarks')->nullable();
            $table->text('mojs_remarks')->nullable();

            // Document Uploads & Dates
            $table->string('state_govt_doc_path')->nullable();
            $table->date('state_govt_submission_date')->nullable();

            $table->string('brahmaputra_board_doc_path')->nullable();
            $table->date('brahmaputra_board_submission_date')->nullable();

            $table->string('mojs_doc_path')->nullable();
            $table->date('mojs_submission_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fmbap_projects');
    }
};