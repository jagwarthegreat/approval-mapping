<?php

namespace Jguapin\ApprovalMapping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalMappingVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version'          => ['required', 'string', 'max:100'],
            'company_id'       => ['nullable', 'integer'],
            'business_unit_id' => ['nullable', 'integer'],
            'effective_from'   => ['required', 'date'],
            'effective_to'     => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active'        => ['sometimes', 'boolean'],
            'notes'            => ['nullable', 'string'],
            'module_id'        => ['nullable', 'integer'],
            'module_reference' => ['nullable', 'string', 'max:190'],
        ];
    }
}
