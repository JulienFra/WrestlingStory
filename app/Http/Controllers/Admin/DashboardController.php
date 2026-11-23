<?php

namespace App\Http\Controllers\Admin;

use App\Ai\AiErrorKind;
use App\Ai\AiGateway;
use App\Http\Controllers\Controller;
use App\Models\AiCall;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The AI cost dashboard (O4). Every figure is an SQL aggregate over ai_calls.
 */
class DashboardController extends Controller
{
    /** Shown instead of a name for the calls of a deleted account. */
    public const DELETED_ACCOUNT = 'Compte supprimé';

    /** Failed calls, without the quota refusals. Needs the binding AiGateway::QUOTA_ERROR. */
    private const FAILED_SQL = 'COALESCE(SUM(CASE WHEN NOT ai_calls.success AND (ai_calls.error IS NULL OR ai_calls.error <> ?) THEN 1 ELSE 0 END), 0)';

    /** Calls refused by the quota. Needs the binding AiGateway::QUOTA_ERROR. */
    private const QUOTA_SQL = 'COALESCE(SUM(CASE WHEN ai_calls.error = ? THEN 1 ELSE 0 END), 0)';

    public function __invoke(AiGateway $gateway): Response
    {
        $provider = $gateway->currentProvider();

        return Inertia::render('admin/Index', [
            'totals' => $this->totals(),
            'perDay' => $this->perDay(),
            'perUser' => $this->perUser(),
            'recent' => $this->recent(),
            'provider' => ['value' => $provider->value, 'label' => $provider->label()],
        ]);
    }

    /**
     * @return array{calls: int, succeeded: int, failed: int, quotaRefused: int, cost: float, inputTokens: int, outputTokens: int, failureRate: float}
     */
    private function totals(): array
    {
        $row = $this->calls()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('COUNT(*) AS calls')
            ->selectRaw('COALESCE(SUM(CASE WHEN success THEN 1 ELSE 0 END), 0) AS succeeded')
            ->selectRaw(self::FAILED_SQL.' AS failed', [AiGateway::QUOTA_ERROR])
            ->selectRaw(self::QUOTA_SQL.' AS quota_refused', [AiGateway::QUOTA_ERROR])
            ->selectRaw('COALESCE(SUM(cost), 0) AS cost')
            ->selectRaw('COALESCE(SUM(input_tokens), 0) AS input_tokens')
            ->selectRaw('COALESCE(SUM(output_tokens), 0) AS output_tokens')
            ->first();

        $calls = (int) ($row->calls ?? 0);
        $failed = (int) ($row->failed ?? 0);
        $quotaRefused = (int) ($row->quota_refused ?? 0);
        // The failure rate ignores the quota refusals: the model was not called.
        $attempts = $calls - $quotaRefused;

        return [
            'calls' => $calls,
            'succeeded' => (int) ($row->succeeded ?? 0),
            'failed' => $failed,
            'quotaRefused' => $quotaRefused,
            'cost' => (float) ($row->cost ?? 0),
            'inputTokens' => (int) ($row->input_tokens ?? 0),
            'outputTokens' => (int) ($row->output_tokens ?? 0),
            'failureRate' => $attempts > 0 ? round($failed / $attempts, 4) : 0.0,
        ];
    }

    /**
     * The last 14 days, most recent first, with zeros on the days without calls.
     *
     * @return list<array{date: string, calls: int, failed: int, cost: float}>
     */
    private function perDay(): array
    {
        $rows = $this->calls()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) AS day')
            ->selectRaw('COUNT(*) AS calls')
            ->selectRaw(self::FAILED_SQL.' AS failed', [AiGateway::QUOTA_ERROR])
            ->selectRaw('COALESCE(SUM(cost), 0) AS cost')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy(fn (object $row): string => substr((string) $row->day, 0, 10));

        $days = [];

        for ($i = 0; $i < 14; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $row = $rows->get($date);

            $days[] = [
                'date' => $date,
                'calls' => (int) ($row->calls ?? 0),
                'failed' => (int) ($row->failed ?? 0),
                'cost' => (float) ($row->cost ?? 0),
            ];
        }

        return $days;
    }

    /**
     * The 20 users who cost the most over 30 days. Calls of deleted accounts are grouped together.
     *
     * @return array<int, array{name: string, tier: string|null, calls: int, failed: int, cost: float, callsToday: int, dailyLimit: int|null}>
     */
    private function perUser(): array
    {
        return $this->calls()
            ->leftJoin('users', 'users.id', '=', 'ai_calls.user_id')
            ->where('ai_calls.created_at', '>=', now()->subDays(30))
            ->select('ai_calls.user_id', 'users.name', 'users.tier')
            ->selectRaw('COUNT(*) AS calls')
            ->selectRaw(self::FAILED_SQL.' AS failed', [AiGateway::QUOTA_ERROR])
            ->selectRaw('COALESCE(SUM(ai_calls.cost), 0) AS cost')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN ai_calls.created_at >= ? AND (ai_calls.error IS NULL OR ai_calls.error <> ?) THEN 1 ELSE 0 END), 0) AS calls_today',
                [now()->startOfDay(), AiGateway::QUOTA_ERROR],
            )
            ->groupBy('ai_calls.user_id', 'users.name', 'users.tier')
            ->orderByDesc('cost')
            ->orderByDesc('calls')
            ->limit(20)
            ->get()
            ->map(fn (object $row): array => [
                'name' => $row->user_id === null ? self::DELETED_ACCOUNT : (string) $row->name,
                'tier' => $row->tier === null ? null : (string) $row->tier,
                'calls' => (int) $row->calls,
                'failed' => (int) $row->failed,
                'cost' => (float) $row->cost,
                'callsToday' => (int) $row->calls_today,
                'dailyLimit' => $row->tier === null ? null : (int) config('ai.tiers.'.$row->tier.'.daily_calls', 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recent(): array
    {
        return AiCall::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (AiCall $call): array => [
                'id' => $call->id,
                'createdAt' => $call->created_at?->format('Y-m-d H:i:s'),
                'user' => $call->user->name ?? self::DELETED_ACCOUNT,
                'feature' => $call->feature,
                'provider' => $call->provider,
                'model' => $call->model,
                'inputTokens' => $call->input_tokens,
                'outputTokens' => $call->output_tokens,
                'cost' => $call->cost,
                'durationMs' => $call->duration_ms,
                'success' => $call->success,
                'error' => $this->errorLabel($call->error),
            ])
            ->values()
            ->all();
    }

    private function errorLabel(?string $error): ?string
    {
        if ($error === null) {
            return null;
        }

        if ($error === AiGateway::QUOTA_ERROR) {
            return 'Quota atteint';
        }

        return AiErrorKind::tryFrom($error)?->label() ?? 'Erreur inconnue';
    }

    private function calls(): Builder
    {
        return DB::table('ai_calls');
    }
}
