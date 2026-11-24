<?php

namespace Tests\Feature\Domain;

use App\Enums\RivalryStatus;
use App\Models\Federation;
use App\Models\Rivalry;
use App\Models\Title;
use App\Models\TitleReign;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoFederationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_fictional_federation()
    {
        $this->seed();

        $federation = Federation::query()->sole();
        $this->assertSame('Ligue Fédérale de Catch du Nord', $federation->name);
        $this->assertSame('test@example.com', $federation->user->email);

        $this->assertSame(8, Wrestler::query()->count());
        $this->assertSame(2, Title::query()->count());
        $this->assertSame(1, TitleReign::query()->whereNull('ended_at')->count());
        $this->assertSame(2, Rivalry::query()->where('status', RivalryStatus::Active)->count());
    }
}
