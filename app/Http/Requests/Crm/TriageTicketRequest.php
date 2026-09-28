<?php

namespace App\Http\Requests\Crm;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Partial updates from the ticket sidebar: status, priority, assignee and
 * the resolution summary / customer satisfaction captured on resolve.
 */
class TriageTicketRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'assignee_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'resolution' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'satisfaction' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
        ];
    }
}
