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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attempt_id')
                ->constrained('attempts')
                ->cascadeOnDelete();

            $table->string('question_key', 100);
            $table->longText('answer_value')->nullable();

            $table->timestamps();

            $table->unique(['attempt_id', 'question_key']);
            $table->index('attempt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
