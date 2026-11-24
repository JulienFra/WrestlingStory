<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A main event booked by the player and told by the language model (D-004, O1).
     * type: "simple" (two wrestlers chosen by the player) or "rivalry" (wrestlers and title taken from the rivalry).
     * status: "scheduled" (can still be edited or deleted) or "played" (frozen, with winner, story and follow-up).
     * imposed_winner_id: the winner chosen by the player, or null to let the model choose.
     * follow_up: the model's ideas for what comes next, shown only to the owner.
     * The database checks the shape of a main event; the game rules (same federation, dates, champion in a title match)
     * are up to the code.
     */
    public function up(): void
    {
        Schema::create('main_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federation_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('rivalry_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('wrestler_a_id')->constrained('wrestlers')->cascadeOnDelete();
            $table->foreignId('wrestler_b_id')->constrained('wrestlers')->cascadeOnDelete();
            // Deleting the title keeps the main event and its story, without a title.
            $table->foreignId('title_id')->nullable()->constrained()->nullOnDelete();
            $table->date('scheduled_on');
            $table->foreignId('imposed_winner_id')->nullable()->constrained('wrestlers')->cascadeOnDelete();
            $table->string('status')->default('scheduled');
            $table->foreignId('winner_id')->nullable()->constrained('wrestlers')->cascadeOnDelete();
            $table->text('story')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamp('played_at')->nullable();
            $table->timestamps();

            $table->index(['federation_id', 'status', 'scheduled_on']);
        });

        DB::statement("ALTER TABLE main_events ADD CONSTRAINT main_events_type_check CHECK (type IN ('simple', 'rivalry'))");
        DB::statement("ALTER TABLE main_events ADD CONSTRAINT main_events_status_check CHECK (status IN ('scheduled', 'played'))");
        DB::statement("ALTER TABLE main_events ADD CONSTRAINT main_events_rivalry_check CHECK ((type = 'rivalry') = (rivalry_id IS NOT NULL))");
        DB::statement('ALTER TABLE main_events ADD CONSTRAINT main_events_two_different_wrestlers_check CHECK (wrestler_a_id <> wrestler_b_id)');
        DB::statement('ALTER TABLE main_events ADD CONSTRAINT main_events_imposed_winner_check '
            .'CHECK (imposed_winner_id IS NULL OR imposed_winner_id IN (wrestler_a_id, wrestler_b_id))');
        DB::statement('ALTER TABLE main_events ADD CONSTRAINT main_events_winner_check '
            .'CHECK (winner_id IS NULL OR winner_id IN (wrestler_a_id, wrestler_b_id))');
        // Scheduled: nothing played yet. Played: winner, story and date are all there.
        DB::statement('ALTER TABLE main_events ADD CONSTRAINT main_events_played_check CHECK ('
            ."(status = 'scheduled' AND winner_id IS NULL AND story IS NULL AND follow_up IS NULL AND played_at IS NULL) "
            ."OR (status = 'played' AND winner_id IS NOT NULL AND story IS NOT NULL AND played_at IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('main_events');
    }
};
