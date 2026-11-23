<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per attempt to call the language model (success, failure or refused by the quota).
     * Neither the prompt nor the answer is stored: no personal data in the log.
     * When a player deletes their account, their calls stay counted but anonymous (user_id = null).
     */
    public function up(): void
    {
        Schema::create('ai_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature');
            $table->string('provider');
            $table->string('model');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost', 12, 8)->default(0);
            $table->unsignedInteger('duration_ms');
            $table->boolean('success');
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_calls');
    }
};
