<?php

namespace App\Http\Requests\Crm;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'contact_id')->withoutTrashed()],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'company_id')->withoutTrashed()],
            'deal_id' => ['nullable', 'integer', Rule::exists('deals', 'deal_id')->withoutTrashed()],
            'type' => ['required', Rule::enum(TicketType::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'channel' => ['required', Rule::enum(TicketChannel::class)],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
