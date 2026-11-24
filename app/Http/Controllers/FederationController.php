<?php

namespace App\Http\Controllers;

use App\Enums\MainEventStatus;
use App\Enums\RivalryStatus;
use App\Http\Requests\StoreFederationRequest;
use App\Models\Federation;
use App\Policies\FederationPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mes fédérations": list, create, show and delete the player's federations (D-004).
 */
class FederationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canCreate = Gate::inspect('create', Federation::class);

        $federations = $user->federations()
            ->withCount('wrestlers')
            ->orderBy('name')
            ->get()
            ->map(fn (Federation $federation): array => [
                'id' => $federation->id,
                'name' => $federation->name,
                'description' => $federation->description,
                'wrestlersCount' => (int) $federation->getAttribute('wrestlers_count'),
            ]);

        return Inertia::render('federations/Index', [
            'federations' => $federations,
            'count' => $federations->count(),
            'limit' => FederationPolicy::limitFor($user),
            'tier' => $user->tier->value,
            'canCreate' => $canCreate->allowed(),
            'limitMessage' => $canCreate->allowed() ? null : $canCreate->message(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Federation::class);

        return Inertia::render('federations/Create');
    }

    public function store(StoreFederationRequest $request): RedirectResponse
    {
        // The tier limit is checked by StoreFederationRequest::authorize(), before the fields.
        // The owner always comes from the session, never from the form.
        $federation = $request->user()->federations()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fédération créée.']);

        return to_route('federations.show', $federation);
    }

    public function show(Federation $federation): Response
    {
        Gate::authorize('view', $federation);

        $federation->loadCount([
            'wrestlers',
            'titles',
            'rivalries as active_rivalries_count' => fn ($query) => $query->where('status', RivalryStatus::Active),
            'mainEvents as scheduled_main_events_count' => fn ($query) => $query->where('status', MainEventStatus::Scheduled),
        ]);

        return Inertia::render('federations/Show', [
            'federation' => [
                'id' => $federation->id,
                'name' => $federation->name,
                'description' => $federation->description,
                'wrestlersCount' => (int) $federation->getAttribute('wrestlers_count'),
                'titlesCount' => (int) $federation->getAttribute('titles_count'),
                'activeRivalriesCount' => (int) $federation->getAttribute('active_rivalries_count'),
                'scheduledMainEventsCount' => (int) $federation->getAttribute('scheduled_main_events_count'),
            ],
        ]);
    }

    public function destroy(Federation $federation): RedirectResponse
    {
        Gate::authorize('delete', $federation);

        // The database deletes the wrestlers, titles, reigns, rivalries and main events (cascade).
        $federation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fédération supprimée.']);

        return to_route('federations.index');
    }
}
