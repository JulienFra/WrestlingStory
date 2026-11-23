<?php

namespace App\Http\Controllers;

use App\Ai\AiException;
use App\Ai\AiGateway;
use App\Ai\Prompts\PromoPrompt;
use App\Ai\QuotaExceededException;
use App\Http\Requests\PromoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The promo workshop: the player describes a wrestler, the model writes their promo.
 */
class PromoController extends Controller
{
    public function create(Request $request, AiGateway $gateway): Response
    {
        $user = $request->user();

        return Inertia::render('promo/Create', [
            'remainingToday' => $gateway->remainingToday($user),
            'dailyLimit' => $gateway->dailyLimit($user),
            'result' => $request->session()->get('promo'),
            'oldIdea' => $request->old('idea'),
        ]);
    }

    public function store(PromoRequest $request, AiGateway $gateway): RedirectResponse
    {
        try {
            // Only the player's text goes to the model: never their e-mail, name or id.
            $completion = $gateway->complete(
                user: $request->user(),
                feature: 'promo',
                system: PromoPrompt::SYSTEM,
                prompt: $request->validated('idea'),
                maxTokens: 400,
                maxChars: 2500,
            );
        } catch (QuotaExceededException|AiException $exception) {
            return back()->withInput()->withErrors(['ai' => $exception->getMessage()]);
        }

        return to_route('promo.create')
            ->withInput()
            ->with('promo', [
                'text' => $completion->text,
                'model' => $completion->model,
                'aiGenerated' => true,
            ]);
    }
}
