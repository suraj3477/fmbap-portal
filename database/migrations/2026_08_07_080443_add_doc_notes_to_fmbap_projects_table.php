<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fmbap_projects', function (Blueprint $table) {
            $table->text('state_govt_doc_note')->nullable()->after('state_govt_doc_path');
            $table->text('brahmaputra_board_doc_note')->nullable()->after('brahmaputra_board_doc_path');
            $table->text('mojs_doc_note')->nullable()->after('mojs_doc_path');
        });
    }

    public function down(): void
    {
        Schema::table('fmbap_projects', function (Blueprint $table) {
            $table->dropColumn(['state_govt_doc_note', 'brahmaputra_board_doc_note', 'mojs_doc_note']);
        });
    }
};
