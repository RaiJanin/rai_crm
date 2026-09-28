<?php

namespace App\Services\Crm;

use App\Contracts\Commentable;
use App\Models\Comment;
use App\Models\User;

class CommentService
{
    /**
     * Add a note (or a logged customer reply) and let the record react to it.
     */
    public function add(Commentable $record, User $author, string $body, bool $isPublic = false): Comment
    {
        $comment = new Comment([
            'user_id' => $author->id,
            'body' => $body,
            'is_public' => $isPublic && $record->acceptsCustomerReplies(),
        ]);

        $record->comments()->save($comment);
        $record->commentAdded($comment);

        return $comment;
    }

    public function remove(Comment $comment): void
    {
        $comment->delete();
    }
}
