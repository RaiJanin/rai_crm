<?php

namespace App\Services\Trash;

use App\Models\Ticket;

/**
 * Trashed tickets; their tasks, conversation and files were trashed with them.
 *
 * @extends ContactOwnedBin<Ticket>
 */
class TicketBin extends ContactOwnedBin
{
    protected function model(): string
    {
        return Ticket::class;
    }
}
