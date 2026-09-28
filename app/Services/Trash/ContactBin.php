<?php

namespace App\Services\Trash;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trashed contacts; restoring one brings back the deals, tickets and tasks
 * that were trashed with it.
 *
 * @extends TrashBin<Contact>
 */
class ContactBin extends TrashBin
{
    protected function model(): string
    {
        return Contact::class;
    }

    protected function withDetails(Builder $query): Builder
    {
        return $query->with(['company' => fn ($query) => $query->withTrashed()->select('company_id', 'name')]);
    }
}
