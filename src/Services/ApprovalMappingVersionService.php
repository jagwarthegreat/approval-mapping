<?php

namespace Jguapin\ApprovalMapping\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Jguapin\ApprovalMapping\Models\ApprovalMapping;
use Jguapin\ApprovalMapping\Models\ApprovalMappingLevel;
use Jguapin\ApprovalMapping\Models\ApprovalMappingVersion;
use Jguapin\ApprovalMapping\Support\ModelResolver;

class ApprovalMappingVersionService
{
    public function __construct(private readonly ApprovalMappingLookupService $lookupService) {}

    public function paginateVersions(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = ApprovalMappingVersion::query()
            ->with($this->relationNames())
            ->orderByDesc('effective_from');

        if (! empty($filters['search'])) {
            $keyword = '%'.strtolower((string) $filters['search']).'%';
            $query->where(function ($builder) use ($keyword) {
                $builder->whereRaw('LOWER(version) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(notes, \'\')) LIKE ?', [$keyword]);
            });
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['business_unit_id'])) {
            $query->where('business_unit_id', (int) $filters['business_unit_id']);
        }

        if (! empty($filters['module_reference'])) {
            $query->where('module_reference', (string) $filters['module_reference']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate(max($perPage, 1));
    }

    public function createVersion(array $payload): ApprovalMappingVersion
    {
        $isActive = (bool) ($payload['is_active'] ?? false);
        $payload['is_active'] = false;
        $payload = $this->applyModulePayload($payload);

        $version = ApprovalMappingVersion::create($payload);

        if ($isActive) {
            $this->activate($version);
        }

        return $version->fresh($this->relationNames());
    }

    public function updateVersion(ApprovalMappingVersion $version, array $payload): ApprovalMappingVersion
    {
        if (! empty($payload['is_active'])) {
            $this->activate($version);
            unset($payload['is_active']);
        }

        if (array_key_exists('module_reference', $payload)) {
            $payload = $this->applyModulePayload($payload);
        }

        $version->update($payload);

        return $version->fresh($this->relationNames());
    }

    public function deleteVersion(ApprovalMappingVersion $version): void
    {
        $version->delete();
    }

    public function activate(ApprovalMappingVersion $version): ApprovalMappingVersion
    {
        ApprovalMappingVersion::query()
            ->where('id', '!=', $version->id)
            ->when($version->module_reference, fn ($q) => $q->where('module_reference', $version->module_reference), fn ($q) => $q->whereNull('module_reference'))
            ->when($version->company_id, fn ($q) => $q->where('company_id', $version->company_id), fn ($q) => $q->whereNull('company_id'))
            ->when($version->business_unit_id, fn ($q) => $q->where('business_unit_id', $version->business_unit_id), fn ($q) => $q->whereNull('business_unit_id'))
            ->update(['is_active' => false]);

        $version->update(['is_active' => true]);

        return $version->fresh($this->relationNames());
    }

    public function getDetails(ApprovalMappingVersion $version): array
    {
        $version->load(['approvalMappings.approvalMappingLevel', 'approvalMappings.branch', ...$this->relationNames()]);

        $levelColumns = $version->getLevelColumns();
        $mappedRows = $this->mappedRowsFromVersion($version, $levelColumns);
        $rows = [];

        $departments = $this->lookupService->departmentsForVersion(
            $version->company_id ? (int) $version->company_id : null,
            $version->business_unit_id ? (int) $version->business_unit_id : null
        );
        if ($departments->isNotEmpty()) {
            foreach ($departments as $department) {
                $label = $this->departmentLabel($department);
                $match = $mappedRows[$label] ?? null;

                $rows[] = [
                    'department' => $label,
                    'branch_id' => $match['branch_id'] ?? null,
                    'branch_name' => $match['branch_name'] ?? '',
                    'type' => $match['type'] ?? 'direct',
                    'cells' => $match['cells'] ?? $this->emptyCells($levelColumns),
                ];
            }
        } else {
            $rows = array_values($mappedRows);
        }

        return [
            'version' => $version,
            'level_columns' => $levelColumns,
            'rows' => $rows,
        ];
    }

    public function saveMappingsLevels(ApprovalMappingVersion $version, array $rows): void
    {
        $version->load('module');
        $moduleStr = $version->module_code ?? '';

        DB::transaction(function () use ($version, $rows, $moduleStr) {
            $processedKeys = [];

            foreach ($rows as $row) {
                $department = trim((string) ($row['department'] ?? ''));
                $branchId = (int) ($row['branch_id'] ?? 0);
                $type = strtolower((string) ($row['type'] ?? 'direct')) === 'agency' ? 'agency' : 'direct';
                $levels = (array) ($row['levels'] ?? $row['cells'] ?? []);

                if ($department === '' || ! $branchId) {
                    continue;
                }

                $levelGroups = $this->normalizeLevelGroups($levels);
                if ($levelGroups === []) {
                    continue;
                }

                $mapping = ApprovalMapping::updateOrCreate(
                    [
                        'version_id' => $version->id,
                        'department' => $department,
                        'branch_id' => $branchId,
                        'type' => $type,
                        'cost_range_id' => null,
                    ],
                    [
                        'business_unit_id' => $version->business_unit_id,
                        'module' => $moduleStr,
                    ]
                );

                ApprovalMappingLevel::updateOrCreate(
                    ['approval_mapping_id' => $mapping->id],
                    [
                        'level_number' => 1,
                        'level_groups' => $levelGroups,
                    ]
                );

                $processedKeys[] = $department.'|'.$branchId.'|'.$type;
            }

            $existingMappings = ApprovalMapping::query()->where('version_id', $version->id)->get();
            foreach ($existingMappings as $mapping) {
                $key = $mapping->department.'|'.$mapping->branch_id.'|'.$mapping->type;
                if (! in_array($key, $processedKeys, true)) {
                    $mapping->delete();
                }
            }
        });
    }

    public function saveAsNew(array $payload): ApprovalMappingVersion
    {
        return DB::transaction(function () use ($payload) {
            $oldVersion = ApprovalMappingVersion::query()->findOrFail((int) $payload['old_version_id']);
            $oldVersion->load($this->relationNames());

            $effectiveFrom = $payload['effective_from'];
            $deactivateQuery = ApprovalMappingVersion::query()
                ->when($oldVersion->module_reference, fn ($q) => $q->where('module_reference', $oldVersion->module_reference), fn ($q) => $q->whereNull('module_reference'))
                ->when($oldVersion->company_id, fn ($q) => $q->where('company_id', $oldVersion->company_id), fn ($q) => $q->whereNull('company_id'))
                ->when($oldVersion->business_unit_id, fn ($q) => $q->where('business_unit_id', $oldVersion->business_unit_id), fn ($q) => $q->whereNull('business_unit_id'));

            $deactivateQuery->update(['is_active' => false]);
            $oldVersion->update(['effective_to' => date('Y-m-d', strtotime($effectiveFrom.' -1 day'))]);

            $newVersion = ApprovalMappingVersion::create([
                'version' => $payload['new_version'],
                'module_id' => $oldVersion->module_id,
                'module_reference' => $oldVersion->module_reference,
                'company_id' => $oldVersion->company_id,
                'business_unit_id' => $oldVersion->business_unit_id,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_active' => true,
                'notes' => $payload['notes'] ?? null,
            ]);

            $rows = $payload['mappings']['rows'] ?? [];
            if ($rows === []) {
                $details = $this->getDetails($oldVersion);
                $rows = collect($details['rows'])->map(function (array $row) use ($details) {
                    $levels = [];
                    foreach ($details['level_columns'] as $level) {
                        $levels[(string) $level] = $row['cells'][$level] ?? [];
                    }

                    return [
                        'department' => $row['department'],
                        'branch_id' => $row['branch_id'],
                        'type' => $row['type'],
                        'levels' => $levels,
                    ];
                })->all();
            }

            $this->saveMappingsLevels($newVersion, $rows);

            return $newVersion->fresh($this->relationNames());
        });
    }

    public function lookup(string $type, array $params = []): array
    {
        return $this->lookupService->lookup($type, $params);
    }

    private function relationNames(): array
    {
        $relations = [];
        foreach (['company', 'businessUnit', 'module'] as $relation) {
            if (ApprovalMappingVersion::relationModelClass($relation)) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    private function mappedRowsFromVersion(ApprovalMappingVersion $version, array $levelColumns): array
    {
        $rows = [];

        foreach ($version->approvalMappings as $mapping) {
            $levelGroups = $mapping->approvalMappingLevel
                ? (array) $mapping->approvalMappingLevel->level_groups
                : [];

            $cells = $this->emptyCells($levelColumns);
            foreach ($levelColumns as $level) {
                $key = (string) $level;
                $cells[$level] = isset($levelGroups[$key]) && is_array($levelGroups[$key])
                    ? array_values(array_map('intval', $levelGroups[$key]))
                    : [];
            }

            $rows[(string) $mapping->department] = [
                'department' => (string) $mapping->department,
                'branch_id' => $mapping->branch_id,
                'branch_name' => $mapping->branch?->name ?? '',
                'type' => $mapping->type ?? 'direct',
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    private function normalizeLevelGroups(array $levels): array
    {
        $levelGroups = [];

        foreach ($levels as $lev => $ids) {
            $levNum = (int) $lev;
            if ($levNum < 1 || $levNum > 6 || ! is_array($ids)) {
                continue;
            }

            $groupIds = array_values(array_filter(array_map('intval', $ids), fn ($id) => $id > 0));
            if ($groupIds !== []) {
                $levelGroups[(string) $levNum] = $groupIds;
            }
        }

        return $levelGroups;
    }

    private function emptyCells(array $levelColumns): array
    {
        $cells = [];
        foreach ($levelColumns as $level) {
            $cells[$level] = [];
        }

        return $cells;
    }

    private function departmentLabel(object $department): string
    {
        $code = trim((string) ($department->department_code ?? ''));
        $name = trim((string) ($department->name ?? ''));

        return $code !== '' ? "[{$code}] {$name}" : $name;
    }

    private function applyModulePayload(array $payload): array
    {
        if (! array_key_exists('module_reference', $payload)) {
            return $payload;
        }

        $moduleReference = $payload['module_reference'] ?: null;
        if (! $moduleReference) {
            $payload['module_id'] = null;
            $payload['module_reference'] = null;

            return $payload;
        }

        $module = ModelResolver::query('sidebar_menu')?->where('reference', $moduleReference)->first();
        $payload['module_id'] = $module?->id;
        $payload['module_reference'] = $module?->reference;

        return $payload;
    }
}
