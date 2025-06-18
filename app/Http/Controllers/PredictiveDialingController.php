<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\PredictiveDialingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class PredictiveDialingController extends Controller
{
    private $dialingService;

    public function __construct(PredictiveDialingService $dialingService)
    {
        $this->dialingService = $dialingService;
    }

    public function dashboard()
    {
        $campaigns = Campaign::with(['nasbahs', 'calls'])
            ->where('is_active', true)
            ->get()
            ->map(function ($campaign) {
                $stats = $this->dialingService->getDialingStats($campaign);
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->campaign_name,
                    'product_type' => $campaign->product_type,
                    'stats' => $stats,
                ];
            });

        return Inertia::render('predictive/Dashboard', [
            'campaigns' => $campaigns,
        ]);
    }

    public function start(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $result = $this->dialingService->startPredictiveDialing($campaign);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Predictive dialing started successfully',
                    'stats' => $this->dialingService->getDialingStats($campaign)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not start predictive dialing'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error starting predictive dialing: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stop(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $result = $this->dialingService->stopPredictiveDialing($campaign);
            
            return response()->json([
                'success' => true,
                'message' => 'Predictive dialing stopped successfully',
                'stats' => $this->dialingService->getDialingStats($campaign)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error stopping predictive dialing: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stats(Campaign $campaign): JsonResponse
    {
        try {
            $stats = $this->dialingService->getDialingStats($campaign);
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting stats: ' . $e->getMessage()
            ], 500);
        }
    }
}