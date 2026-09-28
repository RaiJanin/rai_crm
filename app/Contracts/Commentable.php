<?php

namespace App\Contracts;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A record that accepts notes / conversation entries.
 */
interface Commentable
{
    /**
     * @return MorphMany<Comment, *>
     */
    public function comments(): MorphMany;

    /**
     * Whether entries can be logged as replies to the customer (vs internal notes).
     */
    public function acceptsCustomerReplies(): bool;

    /**
     * Hook run after a comment is added, e.g. to stop an SLA clock.
     */
    public function commentAdded(Comment $comment): void;
}
