<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Campaign;
use App\Models\Nasabah;
use Illuminate\support\Facades\DB;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::withCount('nasabahs')->paginate(10);

        return Inertia::render('Campaign/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function showUploadForm()
    {
        return Inertia::render('Campaign/upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'campaign_name' => 'required|string',
            'product_type' => 'required|string',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $file = $request->file('file');
        $rows = Excel::toArray([], $file)[0];

        $headers = array_map(fn($h) => Str::slug(strtolower($h), '_'), $rows[0]);

        $campaign = Campaign::create([
            'name' => $request->campaign_name,
            'product_type' => $request->product_type,
            'dialing_type' => 'predictive',
            'created_by' => auth()->user()->name,
        ]);

        foreach (array_slice($rows, 1) as $row) {
            $data = array_combine($headers, $row);

            $nasabah = new Nasabah();
            $nasabah->campaign_id = $campaign->id;
            $nasabah->name = $data['nama'] ?? 'Unknown';
            $nasabah->phone = preg_replace('/[^0-9]/', '', $data['telepon'] ?? '');
            $nasabah->data_json = json_encode($data);
            $nasabah->is_called = false;
            $nasabah->save();
        }

        return redirect()->route('campaign.index')->with('success', 'Campaign berhasil di-upload.');
    }

    public function routeToAgent()
    {
        $nasabah = Nasabah::where('is_called', false)->first();

        if (!$nasabah) {
             return response()->json(['message' => 'Tidak ada nasabah tersedia'], 404);
            }

        $agent = Agent::where('status', 'idle')->inRandomOrder()->first();

        if (!$agent) {
            return response()->json(['message' => 'Tidak ada agent tersedia'], 404);
        }

        $callerId = CallerId::where('is_active', true)->inRandomOrder()->first();

        $call = Call::create([
            'nasabah_id' => $nasabah->id,
            'agent_id' => $agent->id,
            'caller_id' => $callerId->id,
            'campaign_id' => $nasabah->campaign_id,
            'status' => 'ringing',
        ]);

        $nasabah->update(['is_called' => true]);
        $agent->update(['status' => 'busy']);

        event(new CallRouted($agent, $nasabah)); // step 5 nanti

        return response()->json(['message' => 'Call routed', 'call_id' => $call->id]);
    }   
}
