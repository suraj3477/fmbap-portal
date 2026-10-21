<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('progress_reports', 'bb_inspection_doc_path')) {
                $table->string('bb_inspection_doc_path', 500)->nullable()->after('bb_remarks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_reports', function (Blueprint $table) {
            if (Schema::hasColumn('progress_reports', 'bb_inspection_doc_path')) {
                $table->dropColumn('bb_inspection_doc_path');
            }
        });
    }
};
