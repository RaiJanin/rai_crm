<?php

namespace App\Enums;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quick views on the ticket list, besides filtering on a single status.
 */
enum TicketView: string
{
    case Active = 'active';
    case Breached = 'breached';

    public const DEFAULT = self::Active;

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::Active => $query->active(),
            self::Breached => $query->breached(),
        };
    }

    /**
     * Every value the ticket list's status filter accepts: views and statuses.
     *
     * @return list<string>
     */
    public static function filterValues(): array
    {
        return [...array_column(self::cases(), 'value'), ...array_column(TicketStatus::cases(), 'value')];
    }
}
