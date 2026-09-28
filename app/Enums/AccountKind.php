<?php

namespace App\Enums;

/**
 * The kind of account a report covers.
 */
enum AccountKind: string
{
    case Company = 'company';
    case Contact = 'contact';

    /**
     * Report title shown on screen and on the print letterhead.
     */
    public function reportTitle(): string
    {
        return match ($this) {
            self::Company => 'Company account report',
            self::Contact => 'Customer account report',
        };
    }
}
