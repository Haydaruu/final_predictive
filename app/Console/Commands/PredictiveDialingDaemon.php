<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\PredictiveDialingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PredictiveDialingDaemon extends Command
{
    protected $signature = 'predictive:daemon {--interval=30}';
    protected $description = 'Run predictive dialing daemon that continuously monitors and initiates calls';

    private $shouldStop = false;

    public function handle(PredictiveDialingService $dialingService)
    {
        $interval = (int) $this->option('interval');
        
        $this->info("🚀 Starting Predictive Dialing Daemon (interval: {$interval}s)");
        $this->info("Press Ctrl+C to stop");
        
        // Handle graceful shutdown
        pcntl_signal(SIGTERM, [$this, 'handleShutdown']);
        pcntl_signal(SIGINT, [$this, 'handleShutdown']);
        
        while (!$this->shouldStop) {
            try {
                // Process signals
                pcntl_signal_dispatch();
                
                $this->line("🔄 Checking for active campaigns...");
                
                // Ambil semua campaign yang aktif
                $activeCampaigns = Campaign::where('is_active', true)->get();
                
                foreach ($activeCampaigns as $campaign) {
                    $stats = $dialingService->getDialingStats($campaign);
                    
                    $this->line("📊 Campaign: {$campaign->campaign_name}");
                    $this->line("   - Available Agents: {$stats['available_agents']}");
                    $this->line("   - Active Calls: {$stats['active_calls']}");
                    $this->line("   - Progress: {$stats['called_nasbahs']}/{$stats['total_nasbahs']}");
                    
                    // Mulai predictive dialing jika ada agent tersedia
                    if ($stats['available_agents'] > 0 && $stats['called_nasbahs'] < $stats['total_nasbahs']) {
                        $dialingService->startPredictiveDialing($campaign);
                    }
                }
                
                if ($activeCampaigns->isEmpty()) {
                    $this->line("😴 No active campaigns found");
                }
                
                $this->line("⏰ Waiting {$interval} seconds...\n");
                sleep($interval);
                
            } catch (\Exception $e) {
                $this->error("❌ Error in daemon: " . $e->getMessage());
                Log::error('Predictive dialing daemon error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                sleep(5); // Wait before retrying
            }
        }
        
        $this->info("🛑 Predictive Dialing Daemon stopped");
        return 0;
    }
    
    public function handleShutdown()
    {
        $this->shouldStop = true;
        $this->info("\n🛑 Shutdown signal received, stopping daemon...");
    }
}