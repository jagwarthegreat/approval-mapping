<?php

namespace Jguapin\ApprovalMapping\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Jguapin\ApprovalMapping\Http\Requests\StoreApprovalMappingVersionRequest;
use Jguapin\ApprovalMapping\Http\Resources\ApprovalMappingVersionResource;
use Jguapin\ApprovalMapping\Models\ApprovalMappingVersion;

class ApprovalMappingVersionController extends Controller
{
    public function index(): JsonResponse
    {
        $data = ApprovalMappingVersion::query()
            ->orderByDesc('effective_from')
            ->paginate(15);

        return response()->json($data->through(fn ($item) => new ApprovalMappingVersionResource($item)));
    }

    public function store(StoreApprovalMappingVersionRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $isActive = (bool) ($payload['is_active'] ?? false);
        $payload['is_active'] = false;

        $version = ApprovalMappingVersion::create($payload);

        if ($isActive) {
            ApprovalMappingVersion::query()
                ->where('id', '!=', $version->id)
                ->where('business_unit_id', $version->business_unit_id)
                ->where('module_reference', $version->module_reference)
                ->update(['is_active' => false]);

            $version->update(['is_active' => true]);
        }

        return response()->json([
            'data' => new ApprovalMappingVersionResource($version->fresh()),
            'message' => 'Approval mapping version created successfully.',
        ]);
    }

    public function show(ApprovalMappingVersion $version): JsonResponse
    {
        return response()->json([
            'data' => new ApprovalMappingVersionResource($version),
            'message' => 'Approval mapping version retrieved successfully.',
        ]);
    }

    public function activate(ApprovalMappingVersion $version): JsonResponse
    {
        ApprovalMappingVersion::query()
            ->where('id', '!=', $version->id)
            ->where('business_unit_id', $version->business_unit_id)
            ->where('module_reference', $version->module_reference)
            ->update(['is_active' => false]);

        $version->update(['is_active' => true]);

        return response()->json([
            'data' => new ApprovalMappingVersionResource($version->fresh()),
            'message' => 'Approval mapping version activated successfully.',
        ]);
    }
}
