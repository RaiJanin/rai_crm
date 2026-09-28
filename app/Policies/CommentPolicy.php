<?php

namespace App\Policies;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends OwnedRecordPolicy<Comment>
 */
class CommentPolicy extends OwnedRecordPolicy
{
    /**
     * @param  Comment  $record
     */
    protected function ownerId(Model $record): ?int
    {
        return $record->user_id;
    }
}
