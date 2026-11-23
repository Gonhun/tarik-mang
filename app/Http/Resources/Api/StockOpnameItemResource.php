<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'result' => $this->result, // found | other_site | not_registered
            'scanned_code' => $this->scanned_code,
            'condition' => $this->condition, // baik | rusak | null
            'location_note' => $this->location_note,
            'asset_status_at_scan' => $this->asset_status_at_scan,
            'jobsite_at_scan' => $this->whenLoaded('jobsiteAtScan', fn () => $this->jobsiteAtScan?->only(['id', 'code'])),
            'asset' => $this->whenLoaded('asset', fn () => $this->asset ? new AssetResource($this->asset) : null),
            'scanned_by' => $this->whenLoaded('scanner', fn () => $this->scanner?->only(['id', 'name'])),
            'scanned_at' => $this->scanned_at?->toIso8601String(),
        ];
    }
}
