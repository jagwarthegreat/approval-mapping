<?php

namespace Jguapin\ApprovalMapping\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jguapin\ApprovalMapping\Http\Requests\StoreApprovalMappingVersionRequest;
use Jguapin\ApprovalMapping\Http\Requests\UpdateApprovalMappingVersionRequest;
use Jguapin\ApprovalMapping\Http\Resources\ApprovalMappingVersionResource;
use Jguapin\ApprovalMapping\Models\ApprovalMappingVersion;
use Jguapin\ApprovalMapping\Services\ApprovalMappingVersionService;

class ApprovalMappingVersionController extends Controller
{
    public function __construct(private readonly ApprovalMappingVersionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->service->paginateVersions($request->all(), $perPage);

        return response()->json($paginator->through(fn ($item) => new ApprovalMappingVersionResource($item)));
    }

    public function store(StoreApprovalMappingVersionRequest $request): JsonResponse
    {
        $version = $this->service->createVersion($request->validated());

        return response()->json([
            'data' => new ApprovalMappingVersionResource($version),
            'message' => 'Approval mapping version created successfully.',
        ]);
    }

    public function show(ApprovalMappingVersion $version): JsonResponse
    {
        $version->load(['company', 'businessUnit', 'module']);

        return response()->json([
            'data' => new ApprovalMappingVersionResource($version),
            'message' => 'Approval mapping version retrieved successfully.',
        ]);
    }

    public function update(UpdateApprovalMappingVersionRequest $request, ApprovalMappingVersion $version): JsonResponse
    {
        $version = $this->service->updateVersion($version, $request->validated());

        return response()->json([
            'data' => new ApprovalMappingVersionResource($version),
            'message' => 'Approval mapping version updated successfully.',
        ]);
    }

    public function destroy(ApprovalMappingVersion $version): JsonResponse
    {
        $this->service->deleteVersion($version);

        return response()->json(['message' => 'Approval mapping version deleted successfully.']);
    }

    public function activate(ApprovalMappingVersion $version): JsonResponse
    {
        $version = $this->service->activate($version);

        return response()->json([
            'data' => new ApprovalMappingVersionResource($version),
            'message' => 'Approval mapping version activated successfully.',
        ]);
    }

    public function details(ApprovalMappingVersion $version): JsonResponse
    {
        $details = $this->service->getDetails($version);

        return response()->json([
            'version' => (new ApprovalMappingVersionResource($details['version']))->resolve(),
            'level_columns' => $details['level_columns'],
            'rows' => $details['rows'],
        ]);
    }

    public function saveMappingsLevels(Request $request, ApprovalMappingVersion $version): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.department' => ['required', 'string', 'max:255'],
            'rows.*.branch_id' => ['required', 'integer'],
            'rows.*.type' => ['required', 'in:direct,agency'],
            'rows.*.levels' => ['sometimes', 'array'],
            'rows.*.cells' => ['sometimes', 'array'],
        ]);

        $this->service->saveMappingsLevels($version, $validated['rows']);

        return response()->json(['message' => 'Version details saved.']);
    }

    public function saveAsNew(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_version_id' => ['required', 'integer'],
            'new_version' => ['required', 'string', 'max:100'],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'mappings' => ['nullable', 'array'],
            'mappings.rows' => ['nullable', 'array'],
        ]);

        $newVersion = $this->service->saveAsNew($validated);

        return response()->json([
            'message' => 'New version created successfully',
            'new_version' => new ApprovalMappingVersionResource($newVersion),
        ]);
    }

    public function syncToModule(ApprovalMappingVersion $version): JsonResponse
    {
        if (! $version->supports_sync) {
            return response()->json([
                'message' => 'Sync requires an active version with company, business unit, and module.',
            ], 422);
        }

        return response()->json([
            'message' => 'Sync endpoint is available. Register syncable host models to enable module sync.',
            'synced_count' => 0,
            'module_code' => $version->module_code,
        ]);
    }

    public function lookup(Request $request, string $type): JsonResponse
    {
        return response()->json($this->service->lookup($type, $request->all()));
    }
}
