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
        'effective_to' => 'date',
        'is_active' => 'boolean',
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
        $class = self::relationModelClass('module');

        return $this->belongsTo($class, 'module_reference', 'reference');
    }

    public function getModuleCodeAttribute(): ?string
    {
        if ($this->relationLoaded('module') && $this->module) {
            return $this->module->code ?? $this->module->name ?? null;
        }

        return null;
    }

    public function getSupportsSyncAttribute(): bool
    {
        return (bool) $this->is_active
            && $this->company_id
            && $this->business_unit_id
            && $this->module_reference;
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
            'company' => 'company',
            'businessUnit' => 'business_unit',
            'module' => 'sidebar_menu',
        ];

        return ModelResolver::modelClass($map[$relation]) ?? Model::class;
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
