<?php

namespace App\Services\Trash;

use App\Models\Deal;

/**
 * Trashed deals; their tasks and files were trashed with them.
 *
 * @extends ContactOwnedBin<Deal>
 */
class DealBin extends ContactOwnedBin
{
    protected function model(): string
    {
        return Deal::class;
    }
}
