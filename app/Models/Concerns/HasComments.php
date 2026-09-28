<?php

namespace App\Models\Concerns;

use App\Contracts\Commentable;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Default implementation of {@see Commentable}: internal notes only.
 *
 * @mixin Model
 */
trait HasComments
{
    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function acceptsCustomerReplies(): bool
    {
        return false;
    }

    public function commentAdded(Comment $comment): void
    {
        //
    }
}
