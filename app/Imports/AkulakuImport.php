<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AkulakuImport implements ToCollection
{
    public function __construct($campaignId)
    {
        Log::info('📦 Constructor OK', ['id' => $campaignId]);
        $this->campaignId = $campaignId;
    }

    public function collection(Collection $rows)
    {
        Log::info('📊 Rows count', ['rows' => $rows->count()]);
        foreach ($rows as $row) {
            Log::info('➡️ Row', $row->toArray());
        }
    }
}