<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Call;
use App\Models\CallerId;
use App\Models\Campaign;
use App\Models\Nasbah;
use App\Events\CallRouted;
use App\Events\PredictiveDialStarted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PredictiveDialingService
{
    private $maxConcurrentCalls = 10;
    private $dialingRatio = 1.5; // Rasio panggilan per agent
    
    public function startPredictiveDialing(Campaign $campaign)
    {
        Log::info('🎯 Starting predictive dialing for campaign', ['campaign_id' => $campaign->id]);
        
        // Cek apakah campaign aktif
        if (!$campaign->is_active) {
            Log::warning('❌ Campaign tidak aktif', ['campaign_id' => $campaign->id]);
            return false;
        }
        
        // Hitung berapa banyak agent yang tersedia
        $availableAgents = Agent::where('status', 'idle')->count();
        
        if ($availableAgents === 0) {
            Log::info('⏸️ Tidak ada agent tersedia');
            return false;
        }
        
        // Hitung berapa panggilan yang harus dibuat
        $callsToMake = min(
            ceil($availableAgents * $this->dialingRatio),
            $this->maxConcurrentCalls
        );
        
        Log::info('📊 Predictive dialing stats', [
            'available_agents' => $availableAgents,
            'calls_to_make' => $callsToMake,
            'dialing_ratio' => $this->dialingRatio
        ]);
        
        // Ambil nasabah yang belum dihubungi
        $nasbahs = Nasbah::where('campaign_id', $campaign->id)
            ->where('is_called', false)
            ->limit($callsToMake)
            ->get();
            
        if ($nasbahs->isEmpty()) {
            Log::info('✅ Semua nasabah sudah dihubungi');
            return false;
        }
        
        // Mulai panggilan untuk setiap nasabah
        foreach ($nasbahs as $nasbah) {
            $this->initiateCall($nasbah, $campaign);
        }
        
        event(new PredictiveDialStarted($campaign, $callsToMake));
        
        return true;
    }
    
    private function initiateCall(Nasbah $nasbah, Campaign $campaign)
    {
        try {
            // Pilih caller ID yang aktif secara random
            $callerId = CallerId::where('is_active', true)
                ->inRandomOrder()
                ->first();
                
            if (!$callerId) {
                Log::error('❌ Tidak ada caller ID tersedia');
                return false;
            }
            
            // Buat record call
            $call = Call::create([
                'campaign_id' => $campaign->id,
                'nasbah_id' => $nasbah->id,
                'agent_id' => null, // Akan diisi ketika agent tersedia
                'caller_id' => $callerId->id,
                'status' => 'dialing',
                'call_started_at' => now(),
            ]);
            
            // Mark nasabah sebagai sudah dipanggil
            $nasbah->update(['is_called' => true]);
            
            Log::info('📞 Call initiated', [
                'call_id' => $call->id,
                'nasbah_id' => $nasbah->id,
                'phone' => $nasbah->phone,
                'caller_id' => $callerId->number
            ]);
            
            // Simulasi panggilan (dalam implementasi nyata, ini akan memanggil API telephony)
            $this->simulateCall($call);
            
        } catch (\Exception $e) {
            Log::error('❌ Error initiating call', [
                'nasbah_id' => $nasbah->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function simulateCall(Call $call)
    {
        // Simulasi delay panggilan (2-5 detik)
        $delay = rand(2, 5);
        
        // Simulasi hasil panggilan
        $outcomes = ['answered', 'busy', 'no_answer', 'failed'];
        $weights = [40, 20, 30, 10]; // Persentase kemungkinan
        
        $outcome = $this->weightedRandom($outcomes, $weights);
        
        Log::info('📱 Call simulation', [
            'call_id' => $call->id,
            'delay' => $delay,
            'outcome' => $outcome
        ]);
        
        // Update status call berdasarkan outcome
        if ($outcome === 'answered') {
            $this->handleAnsweredCall($call);
        } else {
            $call->update([
                'status' => $outcome,
                'call_ended_at' => now()
            ]);
        }
    }
    
    private function handleAnsweredCall(Call $call)
    {
        // Cari agent yang tersedia
        $agent = Agent::where('status', 'idle')
            ->inRandomOrder()
            ->first();
            
        if ($agent) {
            // Assign call ke agent
            $call->update([
                'agent_id' => $agent->id,
                'status' => 'connected'
            ]);
            
            // Update status agent
            $agent->update(['status' => 'busy']);
            
            Log::info('🎯 Call routed to agent', [
                'call_id' => $call->id,
                'agent_id' => $agent->id,
                'agent_name' => $agent->name
            ]);
            
            // Trigger event untuk notifikasi real-time
            event(new CallRouted($agent, $call->nasbah, $call));
            
        } else {
            // Tidak ada agent tersedia, masukkan ke queue atau hangup
            $call->update([
                'status' => 'no_agent_available',
                'call_ended_at' => now()
            ]);
            
            Log::warning('⚠️ No agent available for answered call', [
                'call_id' => $call->id
            ]);
        }
    }
    
    private function weightedRandom($values, $weights)
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($values as $index => $value) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $value;
            }
        }
        
        return $values[0];
    }
    
    public function stopPredictiveDialing(Campaign $campaign)
    {
        Log::info('🛑 Stopping predictive dialing', ['campaign_id' => $campaign->id]);
        
        // Update semua call yang masih dialing menjadi cancelled
        Call::where('campaign_id', $campaign->id)
            ->where('status', 'dialing')
            ->update([
                'status' => 'cancelled',
                'call_ended_at' => now()
            ]);
            
        return true;
    }
    
    public function getDialingStats(Campaign $campaign)
    {
        return [
            'total_nasbahs' => $campaign->nasbahs()->count(),
            'called_nasbahs' => $campaign->nasbahs()->where('is_called', true)->count(),
            'active_calls' => Call::where('campaign_id', $campaign->id)
                ->whereIn('status', ['dialing', 'ringing', 'connected'])
                ->count(),
            'completed_calls' => Call::where('campaign_id', $campaign->id)
                ->whereIn('status', ['answered', 'busy', 'no_answer', 'failed'])
                ->count(),
            'available_agents' => Agent::where('status', 'idle')->count(),
            'busy_agents' => Agent::where('status', 'busy')->count(),
        ];
    }
}