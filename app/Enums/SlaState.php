<?php

namespace App\Enums;

use App\Contracts\HasLabel;

/**
 * Where a ticket stands against its service-level targets.
 *
 * Active tickets are on track, due soon or breached; finished tickets either
 * met or missed their resolution target.
 */
enum SlaState: string implements HasLabel
{
    case OnTrack = 'on_track';
    case DueSoon = 'due_soon';
    case Breached = 'breached';
    case Met = 'met';
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::OnTrack => 'On track',
            self::DueSoon => 'Due soon',
            self::Breached => 'SLA breached',
            self::Met => 'SLA met',
            self::Missed => 'SLA missed',
        };
    }

    /**
     * Badge colour on print templates.
     */
    public function tone(): string
    {
        return match ($this) {
            self::OnTrack => 'blue',
            self::DueSoon => 'amber',
            self::Breached, self::Missed => 'red',
            self::Met => 'green',
        };
    }
}
