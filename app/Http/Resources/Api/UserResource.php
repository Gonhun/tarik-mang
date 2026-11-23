<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ringkasan user untuk API mobile (tanpa data sensitif). */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nik' => $this->nik,
            'position' => $this->whenLoaded('position', fn () => $this->position ? ['id' => $this->position->id, 'name' => $this->position->name] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? ['id' => $this->department->id, 'code' => $this->department->code, 'name' => $this->department->name] : null),
            'jobsite' => $this->whenLoaded('jobsite', fn () => $this->jobsite ? ['id' => $this->jobsite->id, 'code' => $this->jobsite->code, 'name' => $this->jobsite->name] : null),
        ];
    }
}
