<?php

namespace Jguapin\ApprovalMapping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestLog extends Model
{
    protected $table = 'ARLPE';

    protected $fillable = [
        'approval_request_id',
        'reference',
        'cycle_no',
        'level',
        'approver_user_id',
        'group_id',
        'action',
        'remarks',
    ];

    protected $casts = [
        'cycle_no' => 'integer',
        'level' => 'integer',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function getConnectionName()
    {
        return config('approval-mapping.connection');
    }
}
