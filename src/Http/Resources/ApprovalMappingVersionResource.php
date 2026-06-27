<?php

namespace Jguapin\ApprovalMapping\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Jguapin\ApprovalMapping\Support\ModelResolver;

class ApprovalMappingVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $labelField     = ModelResolver::fieldMap('module', 'label', 'name');
        $codeField      = ModelResolver::fieldMap('module', 'code', 'code');
        $referenceField = ModelResolver::fieldMap('module', 'reference', 'reference');

        return [
            'id'               => $this->id,
            'version'          => $this->version,
            'company_id'       => $this->company_id,
            'business_unit_id' => $this->business_unit_id,
            'effective_from'   => optional($this->effective_from)->format('Y-m-d'),
            'effective_to'     => optional($this->effective_to)->format('Y-m-d'),
            'is_active'        => (bool) $this->is_active,
            'notes'            => $this->notes,
            'module_id'        => $this->module_id,
            'module_reference' => $this->module_reference,
            'supports_sync'    => (bool) $this->supports_sync,

            'company' => $this->when(
                ModelResolver::isEnabled('company'),
                $this->whenLoaded('company', fn () => [
                    'id'           => $this->company?->id,
                    'name'         => $this->company->{ModelResolver::fieldMap('company', 'label', 'name')} ?? '',
                    'company_code' => $this->company->{ModelResolver::fieldMap('company', 'code', 'company_code')} ?? '',
                ])
            ),

            'business_unit' => $this->when(
                ModelResolver::isEnabled('business_unit'),
                $this->whenLoaded('businessUnit', fn () => [
                    'id'            => $this->businessUnit?->id,
                    'name'          => $this->businessUnit->{ModelResolver::fieldMap('business_unit', 'label', 'name')} ?? '',
                    'bus_unit_code' => $this->businessUnit->{ModelResolver::fieldMap('business_unit', 'code', 'bus_unit_code')} ?? '',
                ])
            ),

            'module' => $this->when(
                ModelResolver::isEnabled('module'),
                $this->whenLoaded('module', fn () => [
                    'id'        => $this->module?->id,
                    'reference' => $this->module->{$referenceField} ?? null,
                    'name'      => $this->module->{$labelField} ?? '',
                    'code'      => $this->module->{$codeField} ?? '',
                ])
            ),
        ];
    }
}
