<?php

namespace App\Console\Commands;

use App\Models\TrafficEvent;
use Illuminate\Console\Command;

class PruneTrafficEvents extends Command
{
    protected $signature = 'traffic:prune';

    protected $description = 'Delete traffic events older than 90 days';

    public function handle(): int
    {
        $deleted = TrafficEvent::query()
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        $this->info("Pruned {$deleted} traffic events older than 90 days.");

        return self::SUCCESS;
    }
}
