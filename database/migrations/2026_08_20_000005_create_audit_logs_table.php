<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic: can attach to PaymentRequest, ProgressReport, BbMonitoringReport
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->index(['auditable_type', 'auditable_id']);

            // Who did it
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable(); // cache name in case user is deleted

            // What happened
            $table->string('action'); // created, updated, status_changed, document_uploaded, submitted, forwarded, approved, rejected, needs_correction
            $table->string('field_name')->nullable();   // which field changed
            $table->text('old_value')->nullable();      // previous value
            $table->text('new_value')->nullable();      // new value
            $table->text('remarks')->nullable();        // additional context

            // Meta
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
