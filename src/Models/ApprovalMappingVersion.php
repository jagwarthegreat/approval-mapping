<?php

namespace Jguapin\ApprovalMapping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function approvalMappings(): HasMany
    {
        return $this->hasMany(ApprovalMapping::class, 'version_id');
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
