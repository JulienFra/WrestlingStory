<?php

namespace Database\Seeders;

use App\Enums\Alignment;
use App\Enums\MainEventStatus;
use App\Enums\MainEventType;
use App\Models\Federation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A fictional demo federation for the test user: 8 invented wrestlers, 2 titles (one with a champion),
 * 2 active rivalries, a title match coming up and a match already played.
 * Nothing real: no real promotion, no real wrestler.
 */
class DemoFederationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $federation = $user->federations()->create([
            'name' => 'Ligue Fédérale de Catch du Nord',
            'description' => 'Une petite fédération du Nord qui remplit les salles des fêtes le samedi soir.',
        ]);

        $wrestlers = [];
        foreach ($this->roster() as $ringName => [$alignment, $gimmick, $stats]) {
            [$power, $technique, $charisma, $stamina, $popularity] = $stats;

            $wrestlers[$ringName] = $federation->wrestlers()->create([
                'ring_name' => $ringName,
                'gimmick' => $gimmick,
                'alignment' => $alignment,
                'power' => $power,
                'technique' => $technique,
                'charisma' => $charisma,
                'stamina' => $stamina,
                'popularity' => $popularity,
            ]);
        }

        $heavyweight = $federation->titles()->create(['name' => 'Championnat Poids Lourds du Nord', 'prestige' => 70]);
        $federation->titles()->create(['name' => 'Ceinture des Terrils', 'prestige' => 40]);

        $heavyweight->reigns()->create([
            'wrestler_id' => $wrestlers['Le Baron de Roubaix']->id,
            'started_at' => now()->subWeeks(6),
        ]);

        $titleFeud = $federation->rivalries()->create([
            'wrestler_a_id' => $wrestlers['Le Baron de Roubaix']->id,
            'wrestler_b_id' => $wrestlers['Marius Tonnerre']->id,
            'title_id' => $heavyweight->id,
            'intensity' => 75,
        ]);
        $federation->rivalries()->create([
            'wrestler_a_id' => $wrestlers['La Mouette Masquée']->id,
            'wrestler_b_id' => $wrestlers['Docteur Brouillard']->id,
            'intensity' => 55,
        ]);

        // Coming up next Saturday: the title match of the rivalry.
        $federation->mainEvents()->create([
            'type' => MainEventType::Rivalry,
            'rivalry_id' => $titleFeud->id,
            'wrestler_a_id' => $titleFeud->wrestler_a_id,
            'wrestler_b_id' => $titleFeud->wrestler_b_id,
            'title_id' => $heavyweight->id,
            'scheduled_on' => now()->next(Carbon::SATURDAY)->toDateString(),
        ]);

        // Already played: status, winner and story are written by the game, not by the player (not fillable).
        $federation->mainEvents()->make([
            'type' => MainEventType::Simple,
            'wrestler_a_id' => $wrestlers['Gaston Brique']->id,
            'wrestler_b_id' => $wrestlers['Fernand Pavé']->id,
            'scheduled_on' => now()->subWeek()->toDateString(),
        ])->forceFill([
            'status' => MainEventStatus::Played,
            'winner_id' => $wrestlers['Gaston Brique']->id,
            'story' => 'Dans une salle des fêtes comble, Gaston Brique encaisse trois chaises avant de clouer Fernand Pavé au sol d\'un tombé rageur.',
            'follow_up' => 'Fernand Pavé pourrait accuser l\'arbitre et réclamer une revanche sans disqualification.',
            'played_at' => now()->subWeek(),
        ])->save();
    }

    /**
     * Ring name => [alignment, gimmick, [power, technique, charisma, stamina, popularity]].
     *
     * @return array<string, array{Alignment, string, array{int, int, int, int, int}}>
     */
    private function roster(): array
    {
        return [
            'Le Baron de Roubaix' => [Alignment::Heel, 'Aristocrate arrogant qui méprise le public et ne combat qu\'en redingote.', [78, 70, 85, 65, 80]],
            'Marius Tonnerre' => [Alignment::Face, 'Ancien docker au grand cœur, chouchou des tribunes populaires.', [88, 55, 75, 80, 78]],
            'La Mouette Masquée' => [Alignment::Face, 'Voltigeuse masquée venue du littoral, insaisissable sur la troisième corde.', [50, 85, 70, 75, 72]],
            'Docteur Brouillard' => [Alignment::Heel, 'Faux médecin aux méthodes douteuses qui « soigne » ses adversaires.', [60, 80, 65, 60, 58]],
            'Gaston Brique' => [Alignment::Face, 'Maçon colossal, lent mais impossible à faire tomber.', [95, 40, 50, 85, 55]],
            'Victoire Lacroix' => [Alignment::Face, 'Technicienne rigoureuse formée dans les clubs de lutte amateur.', [60, 90, 60, 80, 62]],
            'Le Corbeau du Beffroi' => [Alignment::Heel, 'Silhouette sombre qui descend du clocher pour hanter ses rivaux.', [70, 65, 80, 60, 66]],
            'Fernand Pavé' => [Alignment::Heel, 'Bagarreur des pavés qui préfère les coups bas aux prises.', [82, 45, 55, 70, 48]],
        ];
    }
}
