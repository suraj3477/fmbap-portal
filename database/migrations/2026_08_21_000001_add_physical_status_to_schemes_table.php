<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schemes', function (Blueprint $table) {
            $table->string('physical_status')->default('Completed')->after('project_type');
            $table->decimal('physical_progress_pct', 5, 2)->default(100.00)->after('physical_status');
        });
    }

    public function down(): void
    {
        Schema::table('schemes', function (Blueprint $table) {
            $table->dropColumn(['physical_status', 'physical_progress_pct']);
        });
    }
};
