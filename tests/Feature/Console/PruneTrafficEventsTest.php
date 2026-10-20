<?php

namespace Tests\Feature\Console;

use App\Models\TrafficEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneTrafficEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_removes_events_older_than_90_days(): void
    {
        $oldEvent = TrafficEvent::create([
            'path' => 'api/games',
            'method' => 'GET',
            'status' => 200,
        ]);
        $oldEvent->forceFill(['created_at' => now()->subDays(91)])->save();
        $recentEvent = TrafficEvent::create([
            'path' => 'api/guides',
            'method' => 'GET',
            'status' => 200,
        ]);

        $this->artisan('traffic:prune')
            ->expectsOutput('Pruned 1 traffic events older than 90 days.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('traffic_events', ['id' => $oldEvent->id]);
        $this->assertModelExists($recentEvent);
    }
}
