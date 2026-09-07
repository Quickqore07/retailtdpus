<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class DetailedFinanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('access', 'finance-report.index');
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'pandl_configuration_id' => ['required', 'integer', 'exists:pandl_configurations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pandl_configuration_id.required' => 'P&L Configuration is required',
            'pandl_configuration_id.exists' => 'Selected P&L Configuration does not exist',
            'user_id.exists' => 'Selected user does not exist',
            'company_ids.array' => 'Company IDs must be an array',
            'company_ids.*.exists' => 'One or more selected companies do not exist',
        ];
    }
}
