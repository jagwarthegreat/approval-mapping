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
            'companies'          => $this->companies($params),
            'business-units'     => $this->businessUnits($params),
            'branches'           => $this->branches($params),
            'departments'        => $this->departments($params),
            'modules'            => $this->modules($params),
            'user-assign-groups' => $this->userAssignGroups(),
            default              => [],
        };
    }

    public function departmentsForVersion(?int $companyId, ?int $businessUnitId): Collection
    {
        if (! ModelResolver::isEnabled('department')) {
            return collect();
        }

        if (
            ModelResolver::isEnabled('company') && ModelResolver::isEnabled('business_unit')
            && $companyId && $businessUnitId
        ) {
            $scoped = $this->departmentsFromMapping($companyId, $businessUnitId, null);
            if ($scoped !== []) {
                return collect($scoped)->map(fn (array $item) => (object) [
                    'name'            => $item['text'],
                    'department_code' => $this->extractCodeFromLabel($item['text']),
                ]);
            }
        }

        return $this->allDepartments();
    }

    private function companies(array $params): array
    {
        if (! ModelResolver::isEnabled('company')) {
            return [];
        }

        $query = ModelResolver::query('company');
        if (! $query) {
            return [];
        }

        $labelField = ModelResolver::fieldMap('company', 'label', 'name');
        $codeField  = ModelResolver::fieldMap('company', 'code', 'company_code');

        if (! empty($params['search'])) {
            $keyword = '%'.strtolower((string) $params['search']).'%';
            $query->where(function (Builder $builder) use ($keyword, $labelField, $codeField) {
                $builder->whereRaw("LOWER({$labelField}) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER(COALESCE({$codeField}, '')) LIKE ?", [$keyword]);
            });
        }

        return $query->orderBy($labelField)->get()->map(function ($row) use ($labelField, $codeField) {
            return [
                'value' => $row->id,
                'text'  => $this->formatCodeName($row->{$codeField} ?? '', $row->{$labelField} ?? ''),
            ];
        })->values()->all();
    }

    private function businessUnits(array $params): array
    {
        if (! ModelResolver::isEnabled('business_unit')) {
            return [];
        }

        $companyId = ! empty($params['company_id']) ? (int) $params['company_id'] : null;

        if ($companyId && ModelResolver::isEnabled('company')) {
            $scoped = $this->businessUnitsByCompany($companyId);
            if ($scoped !== []) {
                return $scoped;
            }
        }

        $codeField  = ModelResolver::fieldMap('business_unit', 'code', 'bus_unit_code');
        $labelField = ModelResolver::fieldMap('business_unit', 'label', 'name');

        return $this->mapOptions(
            ModelResolver::query('business_unit'),
            'id',
            [$labelField, $codeField],
            null,
            $codeField
        );
    }

    private function branches(array $params): array
    {
        if (! ModelResolver::isEnabled('branch')) {
            return [];
        }

        $companyId      = ! empty($params['company_id']) ? (int) $params['company_id'] : null;
        $businessUnitId = ! empty($params['business_unit_id']) ? (int) $params['business_unit_id'] : null;

        if ($companyId && $businessUnitId && ModelResolver::isEnabled('company') && ModelResolver::isEnabled('business_unit')) {
            $scoped = $this->branchesByCompanyAndBusinessUnit($companyId, $businessUnitId);
            if ($scoped !== []) {
                return $scoped;
            }
        }

        $codeField  = ModelResolver::fieldMap('branch', 'code', 'branch_code');
        $labelField = ModelResolver::fieldMap('branch', 'label', 'name');

        return $this->mapOptions(
            ModelResolver::query('branch'),
            'id',
            [$labelField, $codeField],
            null,
            $codeField
        );
    }

    private function departments(array $params): array
    {
        if (! ModelResolver::isEnabled('department')) {
            return [];
        }

        $companyId      = ! empty($params['company_id']) ? (int) $params['company_id'] : null;
        $businessUnitId = ! empty($params['business_unit_id']) ? (int) $params['business_unit_id'] : null;
        $branchId       = ! empty($params['branch_id']) ? (int) $params['branch_id'] : null;

        if (
            ModelResolver::isEnabled('company') && ModelResolver::isEnabled('business_unit')
            && $companyId && $businessUnitId
        ) {
            if ($branchId && ModelResolver::isEnabled('branch')) {
                return $this->departmentsFromMapping($companyId, $businessUnitId, $branchId);
            }

            return $this->departmentsFromMapping($companyId, $businessUnitId, null);
        }

        return $this->allDepartments()->map(fn ($item) => [
            'value' => $this->departmentLabel($item),
            'text'  => $this->departmentLabel($item),
        ])->values()->all();
    }

    private function modules(array $params): array
    {
        if (! ModelResolver::isEnabled('module')) {
            return [];
        }

        $query = ModelResolver::query('sidebar_menu') ?? ModelResolver::query('module');
        if (! $query) {
            return [];
        }

        $labelField     = ModelResolver::fieldMap('module', 'label', 'name');
        $codeField      = ModelResolver::fieldMap('module', 'code', 'code');
        $referenceField = ModelResolver::fieldMap('module', 'reference', 'reference');
        $statusCol      = ModelResolver::fieldMap('module', 'status_col', 'status');
        $statusActive   = config('approval-mapping.field_maps.module.status_active', 0);

        $query->where($statusCol, $statusActive)
            ->whereNotNull($codeField)
            ->where($codeField, '!=', '');

        if (! empty($params['search'])) {
            $keyword = '%'.strtolower((string) $params['search']).'%';
            $query->where(function (Builder $builder) use ($keyword, $labelField, $codeField) {
                $builder->whereRaw("LOWER({$labelField}) LIKE ?", [$keyword])
                    ->orWhereRaw("LOWER({$codeField}) LIKE ?", [$keyword]);
            });
        }

        return $query->orderBy($labelField)->get()->map(function ($row) use ($labelField, $codeField, $referenceField) {
            $name  = trim((string) ($row->{$labelField} ?? ''));
            $code  = trim((string) ($row->{$codeField} ?? ''));
            $ref   = $row->{$referenceField} ?? null;
            $label = $code !== '' ? "{$name} ({$code})" : ($name !== '' ? $name : (string) $ref);

            return [
                'value' => $ref,
                'text'  => $label,
                'code'  => $code,
                'name'  => $name,
            ];
        })->values()->all();
    }

    private function userAssignGroups(): array
    {
        $labelField = ModelResolver::fieldMap('user_assign_group', 'label', 'group_name');

        return $this->mapOptions(
            ModelResolver::query('user_assign_group'),
            'id',
            [$labelField]
        );
    }

    private function businessUnitsByCompany(int $companyId): array
    {
        $rows = $this->mappingJoin('business_unit', $companyId, null, null);

        $codeField  = ModelResolver::fieldMap('business_unit', 'code', 'bus_unit_code');
        $labelField = ModelResolver::fieldMap('business_unit', 'label', 'name');

        return collect($rows)->map(function ($row) use ($codeField, $labelField) {
            return [
                'value' => $row->id,
                'text'  => $this->formatCodeName($row->{$codeField} ?? '', $row->{$labelField} ?? ''),
            ];
        })->values()->all();
    }

    private function branchesByCompanyAndBusinessUnit(int $companyId, int $businessUnitId): array
    {
        $rows = $this->mappingJoin('branch', $companyId, $businessUnitId, null);

        $codeField  = ModelResolver::fieldMap('branch', 'code', 'branch_code');
        $labelField = ModelResolver::fieldMap('branch', 'label', 'name');

        return collect($rows)->map(function ($row) use ($codeField, $labelField) {
            return [
                'value' => $row->id,
                'text'  => $this->formatCodeName($row->{$codeField} ?? '', $row->{$labelField} ?? ''),
            ];
        })->values()->all();
    }

    private function departmentsFromMapping(int $companyId, int $businessUnitId, ?int $branchId): array
    {
        $rows = $this->mappingJoin('department', $companyId, $businessUnitId, $branchId);

        $codeField  = ModelResolver::fieldMap('department', 'code', 'department_code');
        $labelField = ModelResolver::fieldMap('department', 'label', 'name');

        return collect($rows)->map(function ($row) use ($codeField, $labelField) {
            $label = $this->formatCodeName($row->{$codeField} ?? '', $row->{$labelField} ?? '');

            return [
                'value' => $label,
                'text'  => $label,
            ];
        })->values()->all();
    }

    private function mappingJoin(string $targetKey, int $companyId, ?int $businessUnitId, ?int $branchId): array
    {
        $mappingClass = ModelResolver::modelClass('company_branch_department');
        $targetClass  = ModelResolver::modelClass($targetKey);
        if (! $mappingClass || ! $targetClass) {
            return [];
        }

        $mappingModel  = new $mappingClass;
        $targetModel   = new $targetClass;
        $mappingTable  = $mappingModel->getTable();
        $targetTable   = $targetModel->getTable();
        $connection    = $mappingModel->getConnectionName();

        $targetIdColumn = match ($targetKey) {
            'business_unit' => 'business_unit_id',
            'branch'        => 'branch_id',
            'department'    => 'department_id',
            default         => null,
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

        $labelField = ModelResolver::fieldMap($targetKey, 'label', 'name');
        $codeField  = ModelResolver::fieldMap($targetKey, 'code', "{$targetKey}_code");

        $selectColumns = ["{$targetTable}.id", "{$targetTable}.{$labelField}", "{$targetTable}.{$codeField}"];

        return $query
            ->select($selectColumns)
            ->distinct()
            ->orderBy("{$targetTable}.{$labelField}")
            ->get()
            ->all();
    }

    private function allDepartments(): Collection
    {
        $query = ModelResolver::query('department');
        if (! $query) {
            return collect();
        }

        $labelField = ModelResolver::fieldMap('department', 'label', 'name');

        return $query->orderBy($labelField)->get();
    }

    private function departmentLabel(object $department): string
    {
        $codeField  = ModelResolver::fieldMap('department', 'code', 'department_code');
        $labelField = ModelResolver::fieldMap('department', 'label', 'name');

        return $this->formatCodeName($department->{$codeField} ?? '', $department->{$labelField} ?? '');
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
                'text'  => $label !== '' ? $label : (string) $row->{$valueKey},
            ];
        })->values()->all();
    }
}
