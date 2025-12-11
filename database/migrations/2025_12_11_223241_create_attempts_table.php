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
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();

            $table->char('token', 64)->unique();
            $table->string('assessment_version', 32);

            $table->string('candidate_name', 200);
            $table->string('candidate_email', 254);

            $table->enum('status', ['in_progress', 'submitted'])->default('in_progress');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->timestamp('last_activity_at')->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->char('active_session_id', 36)->nullable();
            $table->timestamp('active_session_updated_at')->nullable();

            $table->timestamps();

            $table->index('candidate_email');
            $table->index('candidate_name');
            $table->index('status');
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
