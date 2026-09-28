<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records that belong to the user who created them (notes, files):
 * the owner or an admin may delete them.
 *
 * @template TRecord of Model
 */
abstract class OwnedRecordPolicy
{
    /**
     * Id of the user who owns the record.
     *
     * @param  TRecord  $record
     */
    abstract protected function ownerId(Model $record): ?int;

    /**
     * @param  TRecord  $record
     */
    public function delete(User $user, Model $record): bool
    {
        return $user->isAdmin() || $this->ownerId($record) === $user->id;
    }
}
