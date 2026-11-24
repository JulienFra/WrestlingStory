<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the main event and the public link need (JF-004):
     * - federations.public_token: the secret of the public page, null when the link is off;
     * - main_events.ai_call_id: the ai_calls row of the call that told the match;
     * - rivalries.ended_at: when the rivalry ended.
     * The match history is kept: deleting a wrestler who fought or held a title, or a rivalry that has matches,
     * is refused by the database (NO ACTION instead of CASCADE). Deleting a federation or a player still deletes
     * everything inside (see replaceHistoryForeignKeys() for the title reigns).
     */
    public function up(): void
    {
        Schema::table('federations', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique();
        });

        Schema::table('rivalries', function (Blueprint $table) {
            $table->timestamp('ended_at')->nullable();
        });

        Schema::table('main_events', function (Blueprint $table) {
            $table->foreignId('ai_call_id')->nullable()->constrained()->nullOnDelete();
        });

        $this->replaceHistoryForeignKeys(cascade: false);
    }

    public function down(): void
    {
        $this->replaceHistoryForeignKeys(cascade: true);

        Schema::table('main_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_call_id');
        });

        Schema::table('rivalries', function (Blueprint $table) {
            $table->dropColumn('ended_at');
        });

        Schema::table('federations', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }

    /**
     * Recreates the foreign keys from the match history to the wrestlers and rivalries,
     * with ON DELETE CASCADE (the former state) or without (NO ACTION, the default).
     *
     * title_reigns.wrestler_id is checked at the end of the transaction (DEFERRABLE INITIALLY DEFERRED).
     * PostgreSQL checks the NO ACTION keys of a cascade as soon as the cascading delete has run: deleting a federation
     * removes its wrestlers before the reigns, which are two steps away (federation -> titles -> title_reigns),
     * so an immediate check would refuse it. The main events are one step away and are gone in time.
     */
    private function replaceHistoryForeignKeys(bool $cascade): void
    {
        Schema::table('main_events', function (Blueprint $table) use ($cascade) {
            $columns = [
                'wrestler_a_id' => 'wrestlers',
                'wrestler_b_id' => 'wrestlers',
                'imposed_winner_id' => 'wrestlers',
                'winner_id' => 'wrestlers',
                'rivalry_id' => 'rivalries',
            ];

            foreach ($columns as $column => $references) {
                $table->dropForeign([$column]);
                $foreign = $table->foreign($column)->references('id')->on($references);

                if ($cascade) {
                    $foreign->cascadeOnDelete();
                }
            }
        });

        Schema::table('title_reigns', function (Blueprint $table) use ($cascade) {
            $table->dropForeign(['wrestler_id']);
            $foreign = $table->foreign('wrestler_id')->references('id')->on('wrestlers');

            if ($cascade) {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->deferrable()->initiallyImmediate(false);
            }
        });
    }
};
