<?php

namespace Jguapin\ApprovalMapping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalMappingVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['sometimes', 'string', 'max:100'],
            'company_id' => ['nullable', 'integer'],
            'business_unit_id' => ['nullable', 'integer'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'module_id' => ['nullable', 'integer'],
            'module_reference' => ['nullable', 'string', 'max:190'],
        ];
    }
}
