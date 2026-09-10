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
        Schema::create('fmbap_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state')->nullable();
            
            // Core Details
            $table->text('scheme_name')->nullable();
            $table->string('project_type')->nullable(); // e.g. Flood Control, Anti-Erosion
            $table->string('river_basin')->nullable();
            $table->string('district')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->decimal('estimated_cost_cr', 12, 2)->default(0);
            $table->text('description')->nullable();
            
            // Media
            $table->json('photos')->nullable();
            $table->json('videos')->nullable();
            
            // Workflow & Status
            $table->string('status')->default('SUBMITTED_BY_STATE');
            $table->text('bb_remarks')->nullable();
            $table->text('mojs_remarks')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fmbap_proposals');
    }
};
