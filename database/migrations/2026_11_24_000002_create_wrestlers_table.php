<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A wrestler of a federation's roster. Each stat goes from 0 to 100, checked by the database itself.
     * alignment: "face" or "heel" (App\Enums\Alignment); status: "active" or "injured" (App\Enums\WrestlerStatus).
     */
    public function up(): void
    {
        Schema::create('wrestlers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federation_id')->constrained()->cascadeOnDelete();
            $table->string('ring_name', 60);
            $table->text('gimmick');
            $table->string('alignment');
            $table->unsignedSmallInteger('power');
            $table->unsignedSmallInteger('technique');
            $table->unsignedSmallInteger('charisma');
            $table->unsignedSmallInteger('stamina');
            $table->unsignedSmallInteger('popularity');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['federation_id', 'ring_name']);
        });

        DB::statement("ALTER TABLE wrestlers ADD CONSTRAINT wrestlers_alignment_check CHECK (alignment IN ('face', 'heel'))");
        DB::statement("ALTER TABLE wrestlers ADD CONSTRAINT wrestlers_status_check CHECK (status IN ('active', 'injured'))");
        DB::statement('ALTER TABLE wrestlers ADD CONSTRAINT wrestlers_stats_check CHECK ('
            .'power BETWEEN 0 AND 100 AND technique BETWEEN 0 AND 100 AND charisma BETWEEN 0 AND 100 '
            .'AND stamina BETWEEN 0 AND 100 AND popularity BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('wrestlers');
    }
};
