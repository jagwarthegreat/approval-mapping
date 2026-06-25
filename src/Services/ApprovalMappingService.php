<?php

namespace Jguapin\ApprovalMapping\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Jguapin\ApprovalMapping\Models\ApprovalMapping;
use Jguapin\ApprovalMapping\Models\ApprovalMappingVersion;
use Jguapin\ApprovalMapping\Models\ApprovalRequest;

class ApprovalMappingService
{
    public function resolveMapping(array $context, string $moduleCode): ?ApprovalMapping
    {
        $businessUnitId = (int) ($context['business_unit_id'] ?? 0);
        if (! $businessUnitId) {
            return null;
        }

        $activeVersion = ApprovalMappingVersion::query()
            ->where('is_active', true)
            ->where('business_unit_id', $businessUnitId)
            ->when(isset($context['company_id']), function ($query) use ($context) {
                $query->where('company_id', $context['company_id']);
            })
            ->first();

        if (! $activeVersion) {
            return null;
        }

        return ApprovalMapping::query()
            ->with('approvalMappingLevel')
            ->where('version_id', $activeVersion->id)
            ->where('module', $moduleCode)
            ->when(isset($context['branch_id']), fn ($query) => $query->where('branch_id', $context['branch_id']))
            ->when(isset($context['type']), fn ($query) => $query->where('type', strtolower((string) $context['type'])))
            ->first();
    }

    public function hasValidMapping(array $context, string $moduleCode): bool
    {
        return $this->resolveMapping($context, $moduleCode) !== null;
    }

    public function createRequestFor(Model $model, string $moduleCode, array $extraMetadata = []): ?ApprovalRequest
    {
        if (! method_exists($model, 'approvalContext')) {
            return null;
        }

        $context = (array) $model->approvalContext();
        $mapping = $this->resolveMapping($context, $moduleCode);
        if (! $mapping) {
            return null;
        }

        $requesterId = (int) ($model->requested_by ?? $model->created_by ?? Auth::id());
        if (! $requesterId) {
            return null;
        }

        $metadata = array_merge($extraMetadata, [
            'reference_type' => method_exists($model, 'approvalReferenceType') ? $model->approvalReferenceType() : class_basename($model),
            'reference_id' => $model->getKey(),
            'level_groups_snapshot' => $mapping->getLevelGroups(),
        ]);

        $request = ApprovalRequest::create([
            'requester_id' => $requesterId,
            'business_unit' => (string) ($context['business_unit_id'] ?? ''),
            'module' => $moduleCode,
            'amount' => 0,
            'status' => 'pending',
            'current_level' => 1,
            'mapping_version_id' => $mapping->version_id,
            'approval_mapping_id' => $mapping->id,
            'metadata' => $metadata,
        ]);

        if (method_exists($model, 'isFillable') && $model->isFillable('approval_request_id')) {
            $model->update(['approval_request_id' => $request->id]);
        }

        return $request;
    }
}
