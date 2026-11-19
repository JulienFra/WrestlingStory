<?php

namespace App\Concerns;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * The `name` field is the player's pseudo (shown as "Pseudo" in the UI).
     *
     * @return array<string, array<int, Closure|Unique|ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules($userId),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the French error messages for the profile rules.
     *
     * @return array<string, string>
     */
    protected function profileMessages(): array
    {
        return [
            'name.required' => 'Le pseudo est obligatoire.',
            'name.string' => 'Le pseudo doit être un texte.',
            'name.min' => 'Le pseudo doit contenir au moins :min caractères.',
            'name.max' => 'Le pseudo ne peut pas dépasser :max caractères.',
            'name.regex' => 'Le pseudo ne peut contenir que des lettres sans accent, des chiffres et _ (pas d\'espace ni de caractère spécial).',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.string' => 'L\'adresse e-mail doit être un texte.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide (exemple : nom@domaine.be).',
            'email.max' => 'L\'adresse e-mail ne peut pas dépasser :max caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ];
    }

    /**
     * Get the validation rules used to validate user pseudos.
     *
     * A pseudo is unique whatever its case: "Julien" blocks "julien".
     * The database enforces the same rule with a unique index on lower(name).
     *
     * @return array<int, Closure|Unique|ValidationRule|array<mixed>|string>
     */
    protected function nameRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:/^[A-Za-z0-9_]+$/',
            function (string $attribute, mixed $value, Closure $fail) use ($userId): void {
                if (! is_string($value)) {
                    return; // already refused by the "string" rule
                }

                $taken = User::query()
                    ->whereRaw('lower(name) = ?', [mb_strtolower($value)])
                    ->when($userId !== null, fn ($query) => $query->whereKeyNot($userId))
                    ->exists();

                if ($taken) {
                    $fail('Ce pseudo est déjà pris.');
                }
            },
        ];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * "rfc" checks the syntax, "filter" also requires a domain with a dot
     * (julien@site is refused, julien@site.be is accepted).
     *
     * @return array<int, Closure|Unique|ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email:rfc,filter',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
