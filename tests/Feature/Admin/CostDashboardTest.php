<?php

namespace Tests\Feature\Admin;

use App\Enums\Tier;
use App\Models\AiCall;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CostDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.default_provider' => 'fake']);
        $this->travelTo('2026-11-23 10:00:00');
    }

    /**
     * Today: 3 successes, 1 timeout and 1 quota refusal for a free player, 1 success of a deleted account.
     * Five days ago: 2 successes for a pro player. Forty days ago: 1 expensive call, outside the 30 days.
     */
    private function seedCalls(): void
    {
        $free = User::factory()->create(['name' => 'freeplayer']);
        $pro = User::factory()->create(['name' => 'proplayer']);
        $pro->tier = Tier::Pro;
        $pro->save();

        AiCall::factory()->count(2)->for($pro)->create(['cost' => 0.01, 'created_at' => now()->subDays(5)]);
        AiCall::factory()->for($pro)->create(['cost' => 1, 'created_at' => now()->subDays(40)]);
        AiCall::factory()->count(3)->for($free)->create(['cost' => 0.001]);
        AiCall::factory()->for($free)->create(['success' => false, 'error' => 'timeout']);
        AiCall::factory()->create(['user_id' => null, 'cost' => 0.002]);
        AiCall::factory()->for($free)->quotaRefused()->create();
    }

    public function test_dashboard_aggregates_the_last_thirty_days()
    {
        $this->seedCalls();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Index')
                ->where('totals.calls', 8)
                ->where('totals.succeeded', 6)
                ->where('totals.failed', 1)
                ->where('totals.quotaRefused', 1)
                ->where('totals.inputTokens', 350)
                ->where('totals.outputTokens', 840)
                ->where('totals.cost', fn ($cost) => abs($cost - 0.025) < 1e-9)
                ->where('totals.failureRate', fn ($rate) => abs($rate - 0.1429) < 1e-9)
                ->where('provider', ['value' => 'fake', 'label' => 'Faux : réponse normale']));
    }

    public function test_dashboard_lists_the_days_with_zeros_and_most_recent_first()
    {
        $this->seedCalls();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('perDay', 14)
                ->where('perDay.0.date', '2026-11-23')
                ->where('perDay.0.calls', 6)
                ->where('perDay.0.failed', 1)
                ->where('perDay.0.cost', fn ($cost) => abs($cost - 0.005) < 1e-9)
                ->where('perDay.1', ['date' => '2026-11-22', 'calls' => 0, 'failed' => 0, 'cost' => 0])
                ->where('perDay.5.date', '2026-11-18')
                ->where('perDay.5.calls', 2)
                ->where('perDay.13.date', '2026-11-10'));
    }

    public function test_dashboard_ranks_users_by_cost_and_groups_deleted_accounts()
    {
        $this->seedCalls();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('perUser', 3)
                ->where('perUser.0.name', 'proplayer')
                ->where('perUser.0.tier', 'pro')
                ->where('perUser.0.calls', 2)
                ->where('perUser.0.callsToday', 0)
                ->where('perUser.0.dailyLimit', 100)
                ->where('perUser.1.name', 'freeplayer')
                ->where('perUser.1.calls', 5)
                ->where('perUser.1.failed', 1)
                ->where('perUser.1.callsToday', 4)
                ->where('perUser.1.dailyLimit', 10)
                ->where('perUser.2.name', 'Compte supprimé')
                ->where('perUser.2.tier', null)
                ->where('perUser.2.dailyLimit', null));
    }

    public function test_dashboard_lists_the_recent_calls_with_french_errors()
    {
        $this->seedCalls();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('recent', 9)
                ->where('recent.0.user', 'freeplayer')
                ->where('recent.0.success', false)
                ->where('recent.0.error', 'Quota atteint')
                ->where('recent.1.user', 'Compte supprimé')
                ->where('recent.1.error', null)
                ->where('recent.2.error', 'Délai dépassé')
                ->where('recent.2.createdAt', '2026-11-23 10:00:00')
                ->has('recent.0.model')
                ->has('recent.0.durationMs')
                ->has('recent.0.inputTokens'));
    }

    public function test_empty_dashboard_has_zeros_and_no_lists()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('totals.calls', 0)
                ->where('totals.cost', 0)
                ->where('totals.failureRate', 0)
                ->has('perDay', 14)
                ->has('perUser', 0)
                ->has('recent', 0));
    }

    public function test_dashboard_shows_the_provider_chosen_by_the_admin()
    {
        Setting::set('ai.provider', 'fake_outage');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('provider', ['value' => 'fake_outage', 'label' => 'Faux : fournisseur en panne']));
    }
}
