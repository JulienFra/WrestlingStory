<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {email : E-mail of an existing user} {--revoke : Give the user back the simple player role}')]
#[Description('Make a user an administrator, or take the role back with --revoke')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = mb_strtolower((string) $this->argument('email'));
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("Aucun utilisateur avec l'e-mail {$email}.");

            return self::FAILURE;
        }

        $user->role = $this->option('revoke') ? UserRole::User : UserRole::Admin;
        $user->save();

        $this->info($user->isAdmin()
            ? "{$user->email} est maintenant administrateur."
            : "{$user->email} n'est plus administrateur.");

        return self::SUCCESS;
    }
}
