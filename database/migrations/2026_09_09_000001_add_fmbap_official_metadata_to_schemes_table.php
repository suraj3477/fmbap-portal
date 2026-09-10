<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schemes', function (Blueprint $table) {
            $table->string('division')->nullable()->after('district');
            $table->string('plan_period')->nullable()->default('XI Plan')->after('state');
            $table->decimal('estimated_cost_lakh', 12, 2)->nullable()->after('sanctioned_amount_cr');
            $table->decimal('fund_utilised_cs_lakh', 12, 2)->nullable()->after('estimated_cost_lakh');
            $table->decimal('fund_utilised_ss_lakh', 12, 2)->nullable()->after('fund_utilised_cs_lakh');
            $table->decimal('fund_utilised_total_lakh', 12, 2)->nullable()->after('fund_utilised_ss_lakh');
            $table->decimal('fund_req_cs_lakh', 12, 2)->nullable()->after('fund_utilised_total_lakh');
            $table->decimal('fund_req_ss_lakh', 12, 2)->nullable()->after('fund_req_cs_lakh');
            $table->decimal('fund_req_total_lakh', 12, 2)->nullable()->after('fund_req_ss_lakh');
            $table->json('metadata')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('schemes', function (Blueprint $table) {
            $table->dropColumn([
                'division',
                'plan_period',
                'estimated_cost_lakh',
                'fund_utilised_cs_lakh',
                'fund_utilised_ss_lakh',
                'fund_utilised_total_lakh',
                'fund_req_cs_lakh',
                'fund_req_ss_lakh',
                'fund_req_total_lakh',
                'metadata',
            ]);
        });
    }
};
