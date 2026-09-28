<?php

namespace App\Services\Crm;

use App\Enums\DealStage;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RecordService<Deal>
 */
class DealService extends RecordService
{
    /**
     * Won/lost columns on the pipeline only show deals closed this recently.
     */
    public const PIPELINE_CLOSED_DAYS = 90;

    protected function model(): string
    {
        return Deal::class;
    }

    protected function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->with(['contact:contact_id,first_name,last_name', 'company:company_id,name'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereLike('title', "%{$search}%"))
            ->when($filters['stage'] ?? null, fn ($query, $stage) => $query->where('stage', $stage))
            ->latest();
    }

    /**
     * Open deals plus recently closed ones, for the Kanban board.
     *
     * @return Collection<int, Deal>
     */
    public function pipeline(): Collection
    {
        return Deal::query()
            ->with(['contact:contact_id,first_name,last_name', 'company:company_id,name'])
            ->where(fn ($query) => $query
                ->open()
                ->orWhere('closed_at', '>=', now()->subDays(self::PIPELINE_CLOSED_DAYS)))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function moveToStage(Deal $deal, DealStage $stage): Deal
    {
        $deal->update(['stage' => $stage]);

        return $deal;
    }

    /**
     * Load everything the deal page shows.
     */
    public function loadProfile(Deal $deal): Deal
    {
        return $deal->load([
            'contact:contact_id,first_name,last_name,email,phone,company_id',
            'company:company_id,name',
            'creator:id,name',
            'tasks' => fn ($query) => $query->with('responsible:id,name')->orderBy('due_date'),
            'comments.user:id,name',
            'attachments.uploader:id,name',
        ]);
    }
}
