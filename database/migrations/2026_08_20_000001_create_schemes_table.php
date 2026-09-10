<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('scheme_code')->unique();
            $table->text('scheme_name');
            $table->string('river_basin')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->decimal('sanctioned_amount_cr', 12, 2)->default(0);
            $table->integer('central_share_pct')->default(90);
            $table->integer('state_share_pct')->default(10);
            $table->string('project_type')->nullable(); // Flood Control, Anti-Erosion, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schemes');
    }
};
