<?php

namespace App\Services\Trash;

use App\Models\CrmRecord;
use App\Models\Deal;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trash for records that belong to a contact (deals, tickets): they list
 * their contact and cannot be restored while that contact is trashed.
 *
 * @template TRecord of Deal|Ticket
 *
 * @extends TrashBin<TRecord>
 */
abstract class ContactOwnedBin extends TrashBin
{
    protected function withDetails(Builder $query): Builder
    {
        return $query->with(['contact' => fn ($query) => $query->withTrashed()->select('contact_id', 'first_name', 'last_name')]);
    }

    protected function restoreBlocker(CrmRecord $record): ?string
    {
        return $record->contact()->onlyTrashed()->exists() ? 'Restore the contact first.' : null;
    }
}
