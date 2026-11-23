<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_login_page()
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_player_who_is_not_admin_gets_403()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_admin_can_visit_the_admin_page()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/Index'));
    }

    public function test_shared_is_admin_prop_is_true_for_an_admin()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.isAdmin', true));
    }

    public function test_shared_is_admin_prop_is_false_for_a_player()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.isAdmin', false));
    }

    public function test_shared_is_admin_prop_is_false_for_a_guest()
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.isAdmin', false));
    }
}
