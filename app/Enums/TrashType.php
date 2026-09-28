<?php

namespace App\Enums;

use App\Services\Trash\CompanyBin;
use App\Services\Trash\ContactBin;
use App\Services\Trash\DealBin;
use App\Services\Trash\TicketBin;
use App\Services\Trash\TrashBin;

/**
 * Record types that can be browsed in the trash, as used in /trash/{type}.
 */
enum TrashType: string
{
    case Deals = 'deals';
    case Contacts = 'contacts';
    case Companies = 'companies';
    case Tickets = 'tickets';

    /**
     * The trash bin that handles this record type.
     *
     * @return TrashBin<*>
     */
    public function bin(): TrashBin
    {
        return app(match ($this) {
            self::Deals => DealBin::class,
            self::Contacts => ContactBin::class,
            self::Companies => CompanyBin::class,
            self::Tickets => TicketBin::class,
        });
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
