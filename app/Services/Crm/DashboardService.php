<?php

namespace App\Services\Crm;

use App\Enums\DealStage;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\CrmModel;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Items shown in each dashboard list.
     */
    public const LIST_SIZE = 6;

    public const FEED_SIZE = 8;

    /**
     * Headline figures for the stat cards.
     *
     * @return array<string, int|float>
     */
    public function stats(): array
    {
        $wonThisMonth = Deal::where('stage', DealStage::Won)->where('closed_at', '>=', now()->startOfMonth());

        return [
            'openDeals' => Deal::open()->count(),
            'openDealsValue' => (float) Deal::open()->sum('value'),
            'pendingTasks' => Task::pending()->count(),
            'overdueTasks' => Task::pending()->whereDate('due_date', '<', today())->count(),
            'wonThisMonth' => (clone $wonThisMonth)->count(),
            'wonThisMonthValue' => (float) (clone $wonThisMonth)->sum('value'),
            'contacts' => Contact::count(),
            'openTickets' => Ticket::active()->count(),
            'breachedTickets' => Ticket::breached()->count(),
        ];
    }

    /**
     * @return EloquentCollection<int, Deal>
     */
    public function recentDeals(): EloquentCollection
    {
        return Deal::with(['contact:contact_id,first_name,last_name', 'company:company_id,name'])
            ->latest()
            ->limit(self::LIST_SIZE)
            ->get();
    }

    /**
     * @return EloquentCollection<int, Task>
     */
    public function tasksFor(User $user): EloquentCollection
    {
        return $user->tasks()
            ->pending()
            ->with('deal:deal_id,title')
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->limit(self::LIST_SIZE)
            ->get();
    }

    /**
     * Latest notes and newly created deals, merged into one feed.
     *
     * Subjects are any {@see CrmModel}, so a note on a deal, contact, company
     * or ticket is described the same way.
     *
     * @return Collection<int, array{key: string, kind: string, user: string|null, text: string, subject: array{type: string, id: string|null, label: string}|null, at: CarbonImmutable|null}>
     */
    public function activity(): Collection
    {
        $comments = Comment::with(['user:id,name', 'commentable'])
            ->latest()
            ->limit(self::FEED_SIZE)
            ->get()
            ->filter(fn (Comment $comment) => $comment->commentable instanceof CrmModel)
            ->map(fn (Comment $comment) => [
                'key' => "comment-{$comment->comment_id}",
                'kind' => 'comment',
                'user' => $comment->user?->name,
                'text' => $comment->body,
                'subject' => $comment->commentable instanceof CrmModel ? $comment->commentable->toSubject() : null,
                'at' => $comment->created_at,
            ]);

        $deals = Deal::with('creator:id,name')
            ->latest()
            ->limit(self::FEED_SIZE)
            ->get()
            ->map(fn (Deal $deal) => [
                'key' => "deal-{$deal->deal_id}",
                'kind' => 'deal',
                'user' => $deal->creator?->name,
                'text' => 'created a deal',
                'subject' => $deal->toSubject(),
                'at' => $deal->created_at,
            ]);

        return $comments->toBase()->concat($deals)->sortByDesc('at')->take(self::FEED_SIZE)->values();
    }
}
