<?php

namespace Tests\Feature\Ai;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_setting_returns_the_default()
    {
        $this->assertNull(Setting::get('ai.provider'));
        $this->assertSame('fake', Setting::get('ai.provider', 'fake'));
    }

    public function test_set_creates_then_replaces_the_value()
    {
        Setting::set('ai.provider', 'fake_timeout');
        $this->assertSame('fake_timeout', Setting::get('ai.provider'));

        Setting::set('ai.provider', 'openrouter');
        $this->assertSame('openrouter', Setting::get('ai.provider'));
        $this->assertSame(1, Setting::query()->count());
    }

    public function test_null_value_falls_back_to_the_default()
    {
        Setting::set('ai.provider', null);

        $this->assertSame('fake', Setting::get('ai.provider', 'fake'));
    }
}
