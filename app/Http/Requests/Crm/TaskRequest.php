<?php

namespace App\Http\Requests\Crm;

use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:5000'],
            'deal_id' => ['nullable', 'integer', Rule::exists('deals', 'deal_id')->withoutTrashed()],
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'contact_id')->withoutTrashed()],
            'ticket_id' => ['nullable', 'integer', Rule::exists('tickets', 'ticket_id')->withoutTrashed()],
            'responsible_person_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
