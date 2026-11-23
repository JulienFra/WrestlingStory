<?php

namespace App\Http\Controllers\Admin;

use App\Ai\AiGateway;
use App\Ai\AiProvider;
use App\Enums\Tier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAiProviderRequest;
use App\Http\Requests\Admin\UpdateUserTierRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin settings: which provider answers the AI calls, and the tier of each player (O6: no payment).
 */
class SettingsController extends Controller
{
    public function edit(AiGateway $gateway): Response
    {
        $users = User::query()
            ->withCount(['aiCalls as calls_today' => fn ($query) => $query
                ->where('created_at', '>=', now()->startOfDay())
                ->where(fn ($query) => $query->whereNull('error')->orWhere('error', '!=', AiGateway::QUOTA_ERROR))])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'tier' => $user->tier->value,
                'callsToday' => (int) $user->getAttribute('calls_today'),
                'dailyLimit' => $gateway->dailyLimit($user),
            ]);

        return Inertia::render('admin/Settings', [
            'provider' => $gateway->currentProvider()->value,
            'providers' => array_map(
                fn (AiProvider $provider): array => ['value' => $provider->value, 'label' => $provider->label()],
                AiProvider::cases(),
            ),
            'tiers' => array_map(
                fn (Tier $tier): array => ['value' => $tier->value, 'label' => $this->tierLabel($tier)],
                Tier::cases(),
            ),
            'users' => $users,
        ]);
    }

    public function updateAi(UpdateAiProviderRequest $request): RedirectResponse
    {
        $provider = AiProvider::from($request->validated('provider'));

        Setting::set('ai.provider', $provider->value);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Fournisseur d'IA : {$provider->label()}."]);

        return to_route('admin.settings.edit');
    }

    public function updateTier(UpdateUserTierRequest $request, User $user): RedirectResponse
    {
        $user->tier = Tier::from($request->validated('tier'));
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Palier de {$user->name} : {$this->tierLabel($user->tier)}."]);

        return to_route('admin.settings.edit');
    }

    private function tierLabel(Tier $tier): string
    {
        $limit = (int) config("ai.tiers.{$tier->value}.daily_calls", 0);

        return match ($tier) {
            Tier::Free => "Gratuit ({$limit} par jour)",
            Tier::Pro => "Pro ({$limit} par jour)",
        };
    }
}
