<?php

namespace App\Policies;

use App\Enums\Tier;
use App\Models\Federation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may see, create and delete a federation (D-004).
 */
class FederationPolicy
{
    /**
     * The number of federations the player's tier allows (config/game.php).
     */
    public static function limitFor(User $user): int
    {
        return (int) config("game.tiers.{$user->tier->value}.max_federations", 0);
    }

    public function view(User $user, Federation $federation): bool
    {
        return $federation->user_id === $user->id;
    }

    /**
     * Refused once the player owns as many federations as their tier allows.
     * A player back on free keeps their federations, but cannot create more.
     */
    public function create(User $user): Response
    {
        $limit = self::limitFor($user);

        if ($user->federations()->count() < $limit) {
            return Response::allow();
        }

        $proLimit = (int) config('game.tiers.pro.max_federations', 0);

        return Response::deny(match ($user->tier) {
            Tier::Free => "Ton palier Gratuit permet {$limit} fédération. Passe en Pro pour en créer jusqu'à {$proLimit}.",
            Tier::Pro => "Ton palier Pro permet {$limit} fédérations : tu as atteint la limite.",
        });
    }

    public function delete(User $user, Federation $federation): bool
    {
        return $federation->user_id === $user->id;
    }
}
