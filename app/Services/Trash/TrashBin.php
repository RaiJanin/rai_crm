<?php

namespace App\Services\Trash;

use App\Exceptions\RestoreBlockedException;
use App\Models\CrmRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The trash for one record type: list, restore and permanently delete.
 *
 * Each subclass names its model; subclasses override the hooks to eager-load
 * what the trash page shows or to block restores that would orphan data.
 * Resolve a bin through {@see TrashType::bin()}.
 *
 * @template TRecord of CrmRecord
 */
abstract class TrashBin
{
    /**
     * @return class-string<TRecord>
     */
    abstract protected function model(): string;

    /**
     * Eager-loads for the trash listing.
     *
     * @param  Builder<TRecord>  $query
     * @return Builder<TRecord>
     */
    protected function withDetails(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Why the record cannot be restored yet, or null if it can.
     *
     * @param  TRecord  $record
     */
    protected function restoreBlocker(CrmRecord $record): ?string
    {
        return null;
    }

    /**
     * @return LengthAwarePaginator<int, TRecord>
     */
    public function items(int $perPage = 20): LengthAwarePaginator
    {
        return $this->withDetails($this->trashed())
            ->latest('deleted_at')
            ->paginate($perPage);
    }

    /**
     * @return TRecord
     */
    protected function find(string $hash): CrmRecord
    {
        $model = $this->model();

        return $this->trashed()->findOrFail($model::decodeHashId($hash));
    }

    /**
     * Restore a record and the children that were trashed with it.
     *
     * @throws RestoreBlockedException
     */
    public function restore(string $hash): void
    {
        $record = $this->find($hash);

        if ($reason = $this->restoreBlocker($record)) {
            throw new RestoreBlockedException($reason);
        }

        $record->restore();
    }

    /**
     * Permanently delete a record, its children and stored files.
     */
    public function purge(string $hash): void
    {
        $record = $this->find($hash);

        $record->forceDelete();
    }

    /**
     * @return Builder<TRecord>
     */
    protected function trashed(): Builder
    {
        $query = $this->model()::query();
        $query->onlyTrashed();

        return $query;
    }
}
