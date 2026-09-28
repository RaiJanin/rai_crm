<?php

namespace App\Services\Trash;

use App\Models\Company;

/**
 * Trashed companies; their contacts, deals and tickets are never trashed with them.
 *
 * @extends TrashBin<Company>
 */
class CompanyBin extends TrashBin
{
    protected function model(): string
    {
        return Company::class;
    }
}
