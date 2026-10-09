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
        Schema::table('bb_monitoring_reports', function (Blueprint $table) {
            $table->decimal('bb_recommended_amount_cr', 12, 2)->nullable()->after('bb_financial_progress_description');
            $table->text('bb_recommendation_justification')->nullable()->after('bb_recommended_amount_cr');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->decimal('bb_recommended_amount_cr', 12, 2)->nullable()->after('state_remarks');
            $table->decimal('approved_amount_cr', 12, 2)->nullable()->after('bb_recommended_amount_cr');
            $table->decimal('deduction_amount_cr', 12, 2)->nullable()->after('approved_amount_cr');
            $table->string('curtailment_reason')->nullable()->after('deduction_amount_cr');
            $table->string('sanction_order_no')->nullable()->after('curtailment_reason');
            $table->date('sanction_order_date')->nullable()->after('sanction_order_no');
            $table->string('sanction_order_doc_path')->nullable()->after('sanction_order_date');
        });

        Schema::table('payment_request_revisions', function (Blueprint $table) {
            $table->decimal('bb_recommended_amount_cr', 12, 2)->nullable()->after('state_remarks');
            $table->decimal('approved_amount_cr', 12, 2)->nullable()->after('bb_recommended_amount_cr');
            $table->decimal('deduction_amount_cr', 12, 2)->nullable()->after('approved_amount_cr');
            $table->string('curtailment_reason')->nullable()->after('deduction_amount_cr');
            $table->string('sanction_order_no')->nullable()->after('curtailment_reason');
            $table->date('sanction_order_date')->nullable()->after('sanction_order_no');
            $table->string('sanction_order_doc_path')->nullable()->after('sanction_order_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bb_monitoring_reports', function (Blueprint $table) {
            $table->dropColumn(['bb_recommended_amount_cr', 'bb_recommendation_justification']);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn([
                'bb_recommended_amount_cr',
                'approved_amount_cr',
                'deduction_amount_cr',
                'curtailment_reason',
                'sanction_order_no',
                'sanction_order_date',
                'sanction_order_doc_path',
            ]);
        });

        Schema::table('payment_request_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'bb_recommended_amount_cr',
                'approved_amount_cr',
                'deduction_amount_cr',
                'curtailment_reason',
                'sanction_order_no',
                'sanction_order_date',
                'sanction_order_doc_path',
            ]);
        });
    }
};
