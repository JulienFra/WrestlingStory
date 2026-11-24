<?php

namespace Database\Seeders;

use App\Enums\Alignment;
use App\Models\Federation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A fictional demo federation for the test user: 8 invented wrestlers, 2 titles (one with a champion), 2 active rivalries.
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

        $federation->rivalries()->create([
            'wrestler_a_id' => $wrestlers['Le Baron de Roubaix']->id,
            'wrestler_b_id' => $wrestlers['Marius Tonnerre']->id,
            'intensity' => 75,
        ]);
        $federation->rivalries()->create([
            'wrestler_a_id' => $wrestlers['La Mouette Masquée']->id,
            'wrestler_b_id' => $wrestlers['Docteur Brouillard']->id,
            'intensity' => 55,
        ]);
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
