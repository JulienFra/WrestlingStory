<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A championship of a federation. Prestige goes from 0 to 100.
     */
    public function up(): void
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federation_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('prestige')->default(50);
            $table->timestamps();

            $table->unique(['federation_id', 'name']);
        });

        DB::statement('ALTER TABLE titles ADD CONSTRAINT titles_prestige_check CHECK (prestige BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('titles');
    }
};
