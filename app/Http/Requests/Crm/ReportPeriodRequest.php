<?php

namespace App\Http\Requests\Crm;

use App\Services\Reports\ReportPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportPeriodRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(ReportPeriod::values())],
        ];
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::from($this->validated('period'));
    }
}
