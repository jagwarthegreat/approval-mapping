<?php

namespace Jguapin\ApprovalMapping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Jguapin\ApprovalMapping\Support\ModelResolver;

class ApprovalMapping extends Model
{
    protected $table = 'AMPMA';

    protected $fillable = [
        'department',
        'branch_id',
        'type',
        'business_unit_id',
        'module',
        'version_id',
        'cost_range_id',
        'is_sequential',
        'auto_approve_threshold',
        'escalation_days',
    ];

    protected $casts = [
        'is_sequential' => 'boolean',
        'auto_approve_threshold' => 'decimal:2',
        'escalation_days' => 'integer',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ApprovalMappingVersion::class, 'version_id');
    }

    public function approvalMappingLevel(): HasOne
    {
        return $this->hasOne(ApprovalMappingLevel::class, 'approval_mapping_id');
    }

    public function branch(): BelongsTo
    {
        $class = ModelResolver::modelClass('branch') ?? self::class;

        return $this->belongsTo($class, 'branch_id');
    }

    public function getLevelGroups(): array
    {
        $level = $this->approvalMappingLevel;
        if (! $level || ! is_array($level->level_groups)) {
            return [];
        }

        $out = [];
        foreach ($level->level_groups as $lev => $ids) {
            $levNum = (int) $lev;
            if ($levNum >= 1 && is_array($ids)) {
                $out[$levNum] = array_values(array_filter(array_map('intval', $ids)));
            }
        }

        return $out;
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
