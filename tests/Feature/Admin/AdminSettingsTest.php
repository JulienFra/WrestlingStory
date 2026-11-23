<?php

namespace Tests\Feature\Admin;

use App\Enums\Tier;
use App\Models\AiCall;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.default_provider' => 'fake']);
    }

    public function test_player_gets_403_on_every_admin_route()
    {
        $player = User::factory()->create();

        $this->actingAs($player)->get(route('admin.index'))->assertForbidden();
        $this->actingAs($player)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($player)->put(route('admin.settings.ai.update'), ['provider' => 'fake_outage'])->assertForbidden();
        $this->actingAs($player)->put(route('admin.users.tier.update', $player), ['tier' => 'pro'])->assertForbidden();

        $this->assertNull(Setting::get('ai.provider'));
    }

    public function test_guest_is_redirected_from_the_admin_settings()
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.ai.update'), ['provider' => 'fake_outage'])->assertRedirect(route('login'));
    }

    public function test_settings_page_lists_the_providers_and_the_users()
    {
        $admin = User::factory()->admin()->create(['name' => 'aadmin']);
        $player = User::factory()->create(['name' => 'bplayer']);
        AiCall::factory()->count(2)->for($player)->create();
        AiCall::factory()->for($player)->quotaRefused()->create();
        Setting::set('ai.provider', 'fake_credit');

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Settings')
                ->where('provider', 'fake_credit')
                ->has('providers', 6)
                ->where('providers.0', ['value' => 'openrouter', 'label' => 'OpenRouter (réel)'])
                ->has('tiers', 2)
                ->has('users', 2)
                ->where('users.1.name', 'bplayer')
                ->where('users.1.role', 'user')
                ->where('users.1.tier', 'free')
                ->where('users.1.callsToday', 2)
                ->where('users.1.dailyLimit', 10));
    }

    public function test_admin_changes_the_ai_provider()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.ai.update'), ['provider' => 'fake_timeout'])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('fake_timeout', Setting::get('ai.provider'));
    }

    public function test_unknown_ai_provider_is_refused()
    {
        Setting::set('ai.provider', 'fake');

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.ai.update'), ['provider' => 'gpt-in-a-box'])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors(['provider' => "Ce fournisseur d'IA n'existe pas."]);

        $this->assertSame('fake', Setting::get('ai.provider'));
    }

    public function test_admin_changes_the_tier_of_a_player()
    {
        $player = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.users.tier.update', $player), ['tier' => 'pro'])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame(Tier::Pro, $player->refresh()->tier);
    }

    public function test_unknown_tier_is_refused()
    {
        $player = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.settings.edit'))
            ->put(route('admin.users.tier.update', $player), ['tier' => 'platinum'])
            ->assertSessionHasErrors(['tier' => "Ce palier n'existe pas."]);

        $this->assertSame(Tier::Free, $player->refresh()->tier);
    }

    public function test_player_cannot_change_their_own_tier()
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->put(route('admin.users.tier.update', $player), ['tier' => 'pro'])
            ->assertForbidden();

        $this->assertSame(Tier::Free, $player->refresh()->tier);
    }
}
