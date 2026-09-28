<?php

namespace App\Enums;

use App\Contracts\HasLabel;

enum TicketType: string implements HasLabel
{
    case Question = 'question';
    case Incident = 'incident';
    case Problem = 'problem';
    case Request = 'request';
    case Complaint = 'complaint';
    case Billing = 'billing';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
