<?php

namespace Jguapin\ApprovalMapping\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jguapin\ApprovalMapping\Models\ApprovalRequest;
use Jguapin\ApprovalMapping\Services\ApprovalMappingService;

trait HasApprovalMapping
{
    public function approval_request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id', 'id');
    }

    public function submitForApproval(array $extraMetadata = []): ?ApprovalRequest
    {
        $moduleCode = property_exists($this, 'approvalModuleCode')
            ? (string) $this->approvalModuleCode
            : 'DEFAULT';

        return app(ApprovalMappingService::class)->createRequestFor($this, $moduleCode, $extraMetadata);
    }
}
