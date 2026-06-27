<?php

namespace Jguapin\ApprovalMapping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jguapin\ApprovalMapping\Support\ModelResolver;

class ApprovalMappingVersion extends Model
{
    protected $table = 'AMVPM';

    protected $fillable = [
        'version',
        'company_id',
        'business_unit_id',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
        'module_id',
        'module_reference',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
    ];

    protected $appends = [
        'supports_sync',
        'module_code',
    ];

    public function approvalMappings(): HasMany
    {
        return $this->hasMany(ApprovalMapping::class, 'version_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(self::relationModelClass('company'), 'company_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(self::relationModelClass('businessUnit'), 'business_unit_id');
    }

    public function module(): BelongsTo
    {
        $class          = self::relationModelClass('module');
        $referenceField = ModelResolver::fieldMap('module', 'reference', 'reference');

        return $this->belongsTo($class, 'module_reference', $referenceField);
    }

    public function getModuleCodeAttribute(): ?string
    {
        if ($this->relationLoaded('module') && $this->module) {
            $codeField  = ModelResolver::fieldMap('module', 'code', 'code');
            $labelField = ModelResolver::fieldMap('module', 'label', 'name');

            return $this->module->{$codeField} ?? $this->module->{$labelField} ?? null;
        }

        return null;
    }

    public function getSupportsSyncAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (ModelResolver::isEnabled('company') && ! $this->company_id) {
            return false;
        }

        if (ModelResolver::isEnabled('business_unit') && ! $this->business_unit_id) {
            return false;
        }

        if (ModelResolver::isEnabled('module') && ! $this->module_reference) {
            return false;
        }

        return true;
    }

    public function getLevelColumns(): array
    {
        $levelNumbers = $this->approvalMappings()
            ->with('approvalMappingLevel')
            ->get()
            ->flatMap(function ($mapping) {
                $level = $mapping->approvalMappingLevel;
                if (! $level || ! is_array($level->level_groups)) {
                    return [];
                }

                return array_map('intval', array_keys($level->level_groups));
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $levelNumbers === [] ? [1] : $levelNumbers;
    }

    public static function relationModelClass(string $relation): string
    {
        $map = [
            'company'      => 'company',
            'businessUnit' => 'business_unit',
            'module'       => 'sidebar_menu',
        ];

        // Fall back to 'module' model key if sidebar_menu is not configured
        if ($relation === 'module' && ! ModelResolver::modelClass('sidebar_menu')) {
            return ModelResolver::modelClass('module') ?? Model::class;
        }

        return ModelResolver::modelClass($map[$relation] ?? '') ?? Model::class;
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
