<?php

namespace App\Enums;

use App\Contracts\HasLabel;

/**
 * Ticket lifecycle, modelled on the Zendesk / Freshdesk flow:
 * New → Open → Pending (waiting on customer) / On hold (waiting on a third
 * party) → Resolved → Closed.
 */
enum TicketStatus: string implements HasLabel
{
    case New = 'new';
    case Open = 'open';
    case Pending = 'pending';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Open => 'Open',
            self::Pending => 'Pending customer',
            self::OnHold => 'On hold',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed], true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->isActive()));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
