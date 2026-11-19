<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify lowercases the email (Str::lower) before validating it, on sign-up, login and password reset.
 * If someone sends email[]=... (by editing the form with the browser dev tools), that call crashes with a 500.
 * This middleware turns that case into a normal validation error first.
 */
class EnsureEmailIsText
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('email') && ! is_string($request->input('email'))) {
            throw ValidationException::withMessages([
                'email' => 'L\'adresse e-mail doit être un texte.',
            ]);
        }

        return $next($request);
    }
}
