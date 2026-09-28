<?php

namespace App\Http\Requests\Crm;

use App\Contracts\Attachable;
use App\Support\MorphResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachable_type' => ['required', Rule::in(app(MorphResolver::class)->aliasesFor(Attachable::class))],
            'attachable_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:'.config('crm.max_attachment_kb')],
        ];
    }

    /**
     * Resolve the record the file is attached to.
     */
    public function attachable(): Attachable
    {
        return app(MorphResolver::class)->resolve(
            (string) $this->validated('attachable_type'),
            (int) $this->validated('attachable_id'),
            Attachable::class,
        );
    }
}
