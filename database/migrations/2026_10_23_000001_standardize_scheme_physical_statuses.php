<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Standardize legacy informal statuses into official departmental terms
        DB::table('schemes')
            ->where('physical_status', 'not done')
            ->where('physical_progress_pct', '>', 0)
            ->update(['physical_status' => 'Ongoing']);

        DB::table('schemes')
            ->whereIn('physical_status', ['not done', 'not completed'])
            ->where('physical_progress_pct', '<=', 0)
            ->update(['physical_status' => 'Not Started']);
            
        DB::table('schemes')
            ->whereIn('physical_status', ['not completed'])
            ->where('physical_progress_pct', '>', 0)
            ->update(['physical_status' => 'Ongoing']);
    }

    public function down(): void
    {
        // No reverse required
    }
};
