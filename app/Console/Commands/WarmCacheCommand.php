<?php

namespace App\Console\Commands;

use App\Services\CachedOrderService;
use Illuminate\Console\Command;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm
                            {--users : Warm cache for all active users}
                            {--dashboard : Warm dashboard statistics}
                            {--all : Warm all caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm application caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Starting cache warming...');

        $warmUsers = $this->option('users') || $this->option('all');
        $warmDashboard = $this->option('dashboard') || $this->option('all');

        if (!$warmUsers && !$warmDashboard) {
            // Default: warm everything
            $warmUsers = true;
            $warmDashboard = true;
        }

        if ($warmDashboard || $warmUsers) {
            $this->info('📊 Warming user caches...');
            $this->warmUserCaches();
        }

        $this->info('✅ Cache warming completed!');

        return Command::SUCCESS;
    }

    /**
     * Warm caches for active users
     */
    protected function warmUserCaches(): void
    {
        $startTime = microtime(true);
        
        CachedOrderService::warmCache();
        
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("   ✓ User caches warmed in {$duration}s");
    }
}
