<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A feud between two wrestlers of the same federation. Intensity goes from 0 to 100.
     * status: "active" or "ended" (App\Enums\RivalryStatus). A wrestler cannot feud with themselves.
     */
    public function up(): void
    {
        Schema::create('rivalries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wrestler_a_id')->constrained('wrestlers')->cascadeOnDelete();
            $table->foreignId('wrestler_b_id')->constrained('wrestlers')->cascadeOnDelete();
            $table->unsignedSmallInteger('intensity')->default(50);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE rivalries ADD CONSTRAINT rivalries_intensity_check CHECK (intensity BETWEEN 0 AND 100)');
        DB::statement("ALTER TABLE rivalries ADD CONSTRAINT rivalries_status_check CHECK (status IN ('active', 'ended'))");
        DB::statement('ALTER TABLE rivalries ADD CONSTRAINT rivalries_two_different_wrestlers_check CHECK (wrestler_a_id <> wrestler_b_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rivalries');
    }
};
