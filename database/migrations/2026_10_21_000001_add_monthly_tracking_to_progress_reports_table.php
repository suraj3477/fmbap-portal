<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('reporting_month')->nullable()->after('reporting_period');
            $table->unsignedSmallInteger('reporting_year')->nullable()->after('reporting_month');
            $table->json('physical_breakup')->nullable()->after('physical_progress_description');
            $table->json('geo_tagged_files')->nullable()->after('progress_report_doc_path');
            $table->string('video_url')->nullable()->after('geo_tagged_files');
            $table->foreignId('last_payment_request_id')->nullable()->after('video_url')->constrained('payment_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            $table->dropForeign(['last_payment_request_id']);
            $table->dropColumn([
                'reporting_month',
                'reporting_year',
                'physical_breakup',
                'geo_tagged_files',
                'video_url',
                'last_payment_request_id',
            ]);
        });
    }
};
