<?php

namespace Jguapin\ApprovalMapping\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalMappingVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'company_id' => $this->company_id,
            'business_unit_id' => $this->business_unit_id,
            'effective_from' => optional($this->effective_from)->toDateString(),
            'effective_to' => optional($this->effective_to)->toDateString(),
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'module_id' => $this->module_id,
            'module_reference' => $this->module_reference,
        ];
    }
}
