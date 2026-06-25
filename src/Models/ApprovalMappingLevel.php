<?php

namespace Jguapin\ApprovalMapping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalMappingLevel extends Model
{
    protected $table = 'AMLPM';

    protected $fillable = [
        'approval_mapping_id',
        'level_number',
        'level_groups',
    ];

    protected $casts = [
        'level_number' => 'integer',
        'level_groups' => 'array',
    ];

    public function approvalMapping(): BelongsTo
    {
        return $this->belongsTo(ApprovalMapping::class, 'approval_mapping_id');
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
