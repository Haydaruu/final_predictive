<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\PredictiveDialingService;
use Illuminate\Console\Command;

class StartPredictiveDialing extends Command
{
    protected $signature = 'predictive:start {campaign_id}';
    protected $description = 'Start predictive dialing for a specific campaign';

    public function handle(PredictiveDialingService $dialingService)
    {
        $campaignId = $this->argument('campaign_id');
        
        $campaign = Campaign::find($campaignId);
        
        if (!$campaign) {
            $this->error("Campaign with ID {$campaignId} not found");
            return 1;
        }
        
        $this->info("Starting predictive dialing for campaign: {$campaign->campaign_name}");
        
        $result = $dialingService->startPredictiveDialing($campaign);
        
        if ($result) {
            $this->info("✅ Predictive dialing started successfully");
        } else {
            $this->warn("⚠️ Predictive dialing could not be started");
        }
        
        return 0;
    }
}