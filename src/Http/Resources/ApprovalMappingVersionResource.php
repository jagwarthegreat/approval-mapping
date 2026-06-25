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
            'effective_from' => optional($this->effective_from)->format('Y-m-d'),
            'effective_to' => optional($this->effective_to)->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'module_id' => $this->module_id,
            'module_reference' => $this->module_reference,
            'supports_sync' => (bool) $this->supports_sync,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'name' => $this->company->name ?? '',
                'company_code' => $this->company->company_code ?? '',
            ]),
            'business_unit' => $this->whenLoaded('businessUnit', fn () => [
                'id' => $this->businessUnit?->id,
                'name' => $this->businessUnit->name ?? '',
                'bus_unit_code' => $this->businessUnit->bus_unit_code ?? '',
            ]),
            'module' => $this->whenLoaded('module', fn () => [
                'id' => $this->module?->id,
                'reference' => $this->module->reference ?? null,
                'name' => $this->module->name ?? '',
                'code' => $this->module->code ?? '',
            ]),
        ];
    }
}
