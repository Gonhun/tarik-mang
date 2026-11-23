<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'status' => $this->status,
            'request_date' => $this->request_date?->toDateString(),
            'needed_date' => $this->needed_date?->toDateString(),
            'purpose' => $this->purpose,
            'final_rejection_notes' => $this->final_rejection_notes,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'submitted_by' => $this->whenLoaded('submitter', fn () => $this->isSubmittedOnBehalf() && $this->submitter ? new UserResource($this->submitter) : null),
            'jobsite' => $this->whenLoaded('jobsite', fn () => $this->jobsite?->only(['id', 'code', 'name'])),
            'items_count' => $this->whenCounted('items', fn ($n) => (int) $n),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'type' => $i->assetType?->only(['id', 'name']),
                'brand' => $i->assetBrand?->only(['id', 'name']),
                'series' => $i->assetSeries?->only(['id', 'name']),
                'quantity' => (int) $i->quantity,
                'item_description' => $i->item_description,
                'is_standard' => (bool) $i->is_standard,
                'it_approval_status' => $i->it_approval_status,
                'fulfillment_type' => $i->fulfillment_type,
            ])),
            'approvals' => $this->whenLoaded('approvals', fn () => $this->approvals->sortBy('step_order')->values()->map(fn ($a) => [
                'id' => $a->id,
                'step_order' => (int) $a->step_order,
                'role' => $a->approver_role_name,
                'status' => $a->status,
                'approver' => $a->approver?->only(['id', 'name']),
                'action_by' => $a->actionBy?->only(['id', 'name']),
                'action_at' => $a->action_at ? \Illuminate\Support\Carbon::parse($a->action_at)->toIso8601String() : null,
                'notes' => $a->notes,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
