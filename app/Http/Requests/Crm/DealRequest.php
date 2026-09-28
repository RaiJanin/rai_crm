<?php

namespace App\Http\Requests\Crm;

use App\Enums\DealStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'contact_id')->withoutTrashed()],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'company_id')->withoutTrashed()],
            'value' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'stage' => ['required', Rule::enum(DealStage::class)],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
