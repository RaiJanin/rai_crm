<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreCommentRequest;
use App\Models\Comment;
use App\Services\Crm\CommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $comments) {}

    public function store(StoreCommentRequest $request): RedirectResponse
    {
        $this->comments->add(
            $request->commentable(),
            $request->user(),
            $request->validated('body'),
            $request->boolean('is_public'),
        );

        return back();
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $this->comments->remove($comment);

        return back();
    }
}
