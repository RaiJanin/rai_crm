<?php

namespace App\Http\Requests\Crm;

use App\Contracts\Commentable;
use App\Support\MorphResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'commentable_type' => ['required', Rule::in(app(MorphResolver::class)->aliasesFor(Commentable::class))],
            'commentable_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Customer replies are only valid on records that keep a customer conversation.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($this->boolean('is_public') && ! $validator->errors()->any() && ! $this->commentable()->acceptsCustomerReplies()) {
                    $validator->errors()->add('is_public', 'Customer replies can only be logged on tickets.');
                }
            },
        ];
    }

    /**
     * Resolve the record being commented on.
     */
    public function commentable(): Commentable
    {
        return app(MorphResolver::class)->resolve(
            (string) $this->input('commentable_type'),
            $this->integer('commentable_id'),
            Commentable::class,
        );
    }
}
