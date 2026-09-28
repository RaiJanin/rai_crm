<?php

namespace App\Enums;

use App\Contracts\HasLabel;

enum TicketPriority: string implements HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Hours until the first reply is due (calendar hours, see config/crm.php).
     */
    public function firstResponseHours(): int
    {
        return (int) config("crm.ticket_sla.{$this->value}.first_response");
    }

    /**
     * Hours until the ticket should be resolved.
     */
    public function resolutionHours(): int
    {
        return (int) config("crm.ticket_sla.{$this->value}.resolution");
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $priority) => ['value' => $priority->value, 'label' => $priority->label()], self::cases());
    }
}
