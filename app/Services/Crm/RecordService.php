<?php

namespace App\Services\Crm;

use App\Models\CrmRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Template for CRUD on a CRM record type.
 *
 * Subclasses name their model and override the hooks where a record type
 * behaves differently: input preparation, list filtering, page size.
 *
 * @template TRecord of CrmRecord
 */
abstract class RecordService
{
    /**
     * @return class-string<TRecord>
     */
    abstract protected function model(): string;

    /**
     * Apply list filters, eager loads and ordering for index pages.
     *
     * @param  Builder<TRecord>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TRecord>
     */
    abstract protected function filter(Builder $query, array $filters): Builder;

    /**
     * Column recording who created the record; null if the model has none.
     */
    protected function creatorColumn(): ?string
    {
        return 'created_by';
    }

    /**
     * Normalise validated input before it is saved (fill defaults, derive fields).
     *
     * @param  array<string, mixed>  $data
     * @param  TRecord|null  $record  The record being updated, null when creating.
     * @return array<string, mixed>
     */
    protected function prepare(array $data, ?CrmRecord $record = null): array
    {
        return $data;
    }

    protected function perPage(): int
    {
        return 15;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  Builder<TRecord>|null  $scope  Pre-scoped query, e.g. "assigned to me".
     * @return LengthAwarePaginator<int, TRecord>
     */
    public function paginate(array $filters, ?Builder $scope = null): LengthAwarePaginator
    {
        return $this->filter($scope ?? $this->query(), $filters)
            ->paginate($this->perPage())
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TRecord
     */
    public function create(array $data, User $creator): CrmRecord
    {
        $attributes = $this->prepare($data);

        if ($column = $this->creatorColumn()) {
            $attributes[$column] = $creator->id;
        }

        return $this->query()->create($attributes);
    }

    /**
     * @param  TRecord  $record
     * @param  array<string, mixed>  $data
     * @return TRecord
     */
    public function update(CrmRecord $record, array $data): CrmRecord
    {
        $record->update($this->prepare($data, $record));

        return $record;
    }

    /**
     * Move a record (and its cascading children) to the trash.
     *
     * @param  TRecord  $record
     */
    public function trash(CrmRecord $record): void
    {
        $record->delete();
    }

    /**
     * @return Builder<TRecord>
     */
    protected function query(): Builder
    {
        return $this->model()::query();
    }
}
