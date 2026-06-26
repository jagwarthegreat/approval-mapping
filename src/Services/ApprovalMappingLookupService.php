<?php

namespace Jguapin\ApprovalMapping\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jguapin\ApprovalMapping\Support\ModelResolver;

class ApprovalMappingLookupService
{
    public function lookup(string $type, array $params = []): array
    {
        return match ($type) {
            'companies' => $this->companies($params),
            'business-units' => $this->businessUnits($params),
            'branches' => $this->branches($params),
            'departments' => $this->departments($params),
            'modules' => $this->modules($params),
            'user-assign-groups' => $this->mapOptions(
                ModelResolver::query('user_assign_group'),
                'id',
                ['group_name']
            ),
            default => [],
        };
    }

    public function departmentsForVersion(?int $companyId, ?int $businessUnitId): Collection
    {
        if ($companyId && $businessUnitId) {
            $scoped = $this->departmentsFromMapping($companyId, $businessUnitId, null);
            if ($scoped !== []) {
                return collect($scoped)->map(fn (array $item) => (object) [
                    'name' => $item['text'],
                    'department_code' => $this->extractCodeFromLabel($item['text']),
                ]);
            }
        }

        return $this->allDepartments();
    }

    private function companies(array $params): array
    {
        $query = ModelResolver::query('company');
        if (! $query) {
            return [];
        }

        if (! empty($params['search'])) {
            $keyword = '%'.strtolower((string) $params['search']).'%';
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->whereRaw('LOWER(name) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(company_code, \'\')) LIKE ?', [$keyword]);
            });
        }

        return $query->orderBy('name')->get()->map(function ($row) {
            return [
                'value' => $row->id,
                'text' => $this->formatCodeName($row->company_code ?? '', $row->name ?? ''),
            ];
        })->values()->all();
    }

    private function businessUnits(array $params): array
    {
        $companyId = ! empty($params['company_id']) ? (int) $params['company_id'] : null;
        if ($companyId) {
            $scoped = $this->businessUnitsByCompany($companyId);
            if ($scoped !== []) {
                return $scoped;
            }
        }

        return $this->mapOptions(
            ModelResolver::query('business_unit'),
            'id',
            ['name', 'bus_unit_code'],
            null,
            'bus_unit_code'
        );
    }

    private function branches(array $params): array
    {
        $companyId = ! empty($params['company_id']) ? (int) $params['company_id'] : null;
        $businessUnitId = ! empty($params['business_unit_id']) ? (int) $params['business_unit_id'] : null;

        if ($companyId && $businessUnitId) {
            $scoped = $this->branchesByCompanyAndBusinessUnit($companyId, $businessUnitId);
            if ($scoped !== []) {
                return $scoped;
            }
        }

        return $this->mapOptions(
            ModelResolver::query('branch'),
            'id',
            ['name', 'branch_code'],
            null,
            'branch_code'
        );
    }

    private function departments(array $params): array
    {
        $companyId = ! empty($params['company_id']) ? (int) $params['company_id'] : null;
        $businessUnitId = ! empty($params['business_unit_id']) ? (int) $params['business_unit_id'] : null;
        $branchId = ! empty($params['branch_id']) ? (int) $params['branch_id'] : null;

        if ($companyId && $businessUnitId && $branchId) {
            return $this->departmentsFromMapping($companyId, $businessUnitId, $branchId);
        }

        if ($companyId && $businessUnitId) {
            return $this->departmentsFromMapping($companyId, $businessUnitId, null);
        }

        return $this->allDepartments()->map(fn ($item) => [
            'value' => $this->departmentLabel($item),
            'text' => $this->departmentLabel($item),
        ])->values()->all();
    }

    private function modules(array $params): array
    {
        $query = ModelResolver::query('sidebar_menu');
        if (! $query) {
            return [];
        }

        $query->where('status', 0)
            ->whereNotNull('code')
            ->where('code', '!=', '');

        if (! empty($params['search'])) {
            $keyword = '%'.strtolower((string) $params['search']).'%';
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->whereRaw('LOWER(name) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$keyword]);
            });
        }

        return $query->orderBy('name')->get()->map(function ($row) {
            $name = trim((string) ($row->name ?? ''));
            $code = trim((string) ($row->code ?? ''));
            $label = $code !== '' ? "{$name} ({$code})" : ($name !== '' ? $name : (string) $row->reference);

            return [
                'value' => $row->reference,
                'text' => $label,
                'code' => $code,
                'name' => $name,
            ];
        })->values()->all();
    }

    private function businessUnitsByCompany(int $companyId): array
    {
        $rows = $this->mappingJoin('business_unit', $companyId, null, null);

        return collect($rows)->map(function ($row) {
            return [
                'value' => $row->id,
                'text' => $this->formatCodeName($row->bus_unit_code ?? '', $row->name ?? ''),
            ];
        })->values()->all();
    }

    private function branchesByCompanyAndBusinessUnit(int $companyId, int $businessUnitId): array
    {
        $rows = $this->mappingJoin('branch', $companyId, $businessUnitId, null);

        return collect($rows)->map(function ($row) {
            return [
                'value' => $row->id,
                'text' => $this->formatCodeName($row->branch_code ?? '', $row->name ?? ''),
            ];
        })->values()->all();
    }

    private function departmentsFromMapping(int $companyId, int $businessUnitId, ?int $branchId): array
    {
        $rows = $this->mappingJoin('department', $companyId, $businessUnitId, $branchId);

        return collect($rows)->map(function ($row) {
            $label = $this->formatCodeName($row->department_code ?? '', $row->name ?? '');

            return [
                'value' => $label,
                'text' => $label,
            ];
        })->values()->all();
    }

    private function mappingJoin(string $targetKey, int $companyId, ?int $businessUnitId, ?int $branchId): array
    {
        $mappingClass = ModelResolver::modelClass('company_branch_department');
        $targetClass = ModelResolver::modelClass($targetKey);
        if (! $mappingClass || ! $targetClass) {
            return [];
        }

        $mappingModel = new $mappingClass;
        $targetModel = new $targetClass;
        $mappingTable = $mappingModel->getTable();
        $targetTable = $targetModel->getTable();
        $connection = $mappingModel->getConnectionName();

        $targetIdColumn = match ($targetKey) {
            'business_unit' => 'business_unit_id',
            'branch' => 'branch_id',
            'department' => 'department_id',
            default => null,
        };

        if (! $targetIdColumn) {
            return [];
        }

        $query = DB::connection($connection)
            ->table($mappingTable)
            ->join($targetTable, "{$mappingTable}.{$targetIdColumn}", '=', "{$targetTable}.id")
            ->where("{$mappingTable}.company_id", $companyId)
            ->whereNull("{$mappingTable}.deleted_at");

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($targetClass), true)) {
            $query->whereNull("{$targetTable}.deleted_at");
        }

        if ($businessUnitId) {
            $query->where("{$mappingTable}.business_unit_id", $businessUnitId);
        }

        if ($branchId) {
            $query->where("{$mappingTable}.branch_id", $branchId);
        }

        $selectColumns = match ($targetKey) {
            'business_unit' => ["{$targetTable}.id", "{$targetTable}.name", "{$targetTable}.bus_unit_code"],
            'branch' => ["{$targetTable}.id", "{$targetTable}.name", "{$targetTable}.branch_code"],
            'department' => ["{$targetTable}.id", "{$targetTable}.name", "{$targetTable}.department_code"],
            default => ["{$targetTable}.id", "{$targetTable}.name"],
        };

        return $query
            ->select($selectColumns)
            ->distinct()
            ->orderBy("{$targetTable}.name")
            ->get()
            ->all();
    }

    private function allDepartments(): Collection
    {
        $query = ModelResolver::query('department');
        if (! $query) {
            return collect();
        }

        return $query->orderBy('name')->get();
    }

    private function departmentLabel(object $department): string
    {
        return $this->formatCodeName($department->department_code ?? '', $department->name ?? '');
    }

    private function formatCodeName(?string $code, ?string $name): string
    {
        $code = trim((string) $code);
        $name = trim((string) $name);

        if ($code !== '' && $name !== '') {
            return "[{$code}] {$name}";
        }

        return $name !== '' ? $name : $code;
    }

    private function extractCodeFromLabel(string $label): string
    {
        if (preg_match('/^\[([^\]]+)\]/', $label, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function mapOptions(
        ?Builder $query,
        string $valueKey,
        array $labelKeys,
        ?string $valueField = null,
        ?string $codeField = null
    ): array {
        if (! $query) {
            return [];
        }

        return $query->orderBy($labelKeys[0])->get()->map(function ($row) use ($valueKey, $labelKeys, $valueField, $codeField) {
            if ($codeField && ! empty($row->{$codeField})) {
                $label = $this->formatCodeName($row->{$codeField}, $row->{$labelKeys[0]} ?? '');
            } else {
                $label = '';
                foreach ($labelKeys as $key) {
                    if (! empty($row->{$key})) {
                        $label = (string) $row->{$key};
                        break;
                    }
                }
            }

            return [
                'value' => $row->{$valueField ?? $valueKey},
                'text' => $label !== '' ? $label : (string) $row->{$valueKey},
            ];
        })->values()->all();
    }
}
