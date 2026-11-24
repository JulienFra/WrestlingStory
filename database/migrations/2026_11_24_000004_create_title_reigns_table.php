<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who held a title, from when to when. ended_at = null: the current champion.
     * The database refuses two open reigns on the same title and a reign that ends before it starts.
     */
    public function up(): void
    {
        Schema::create('title_reigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wrestler_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE title_reigns ADD CONSTRAINT title_reigns_dates_check CHECK (ended_at IS NULL OR ended_at >= started_at)');
        DB::statement('CREATE UNIQUE INDEX title_reigns_one_open_reign_per_title ON title_reigns (title_id) WHERE ended_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('title_reigns');
    }
};
