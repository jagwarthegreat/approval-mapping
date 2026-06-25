<?php

namespace Jguapin\ApprovalMapping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    protected $table = 'APPRO';

    protected $fillable = [
        'requester_id',
        'business_unit',
        'module',
        'amount',
        'status',
        'current_level',
        'mapping_version_id',
        'approval_mapping_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_level' => 'integer',
        'metadata' => 'array',
    ];

    public function approvalMapping(): BelongsTo
    {
        return $this->belongsTo(ApprovalMapping::class);
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(ApprovalMappingVersion::class, 'mapping_version_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalRequestLog::class)->orderByDesc('created_at');
    }

    public function getLevelGroups(): array
    {
        $snapshot = $this->metadata['level_groups_snapshot'] ?? null;
        if (is_array($snapshot)) {
            return $snapshot;
        }

        return $this->approvalMapping?->getLevelGroups() ?? [];
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
