<?php

namespace App\Services\Crm;

use App\Enums\TaskStatus;
use App\Models\CrmRecord;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends RecordService<Task>
 */
class TaskService extends RecordService
{
    protected function model(): string
    {
        return Task::class;
    }

    /**
     * Tasks track who is responsible, not who created them.
     */
    protected function creatorColumn(): ?string
    {
        return null;
    }

    protected function perPage(): int
    {
        return 20;
    }

    /**
     * A task on a deal or ticket defaults to that record's contact.
     */
    protected function prepare(array $data, ?CrmRecord $record = null): array
    {
        if (empty($data['contact_id'])) {
            $data['contact_id'] = match (true) {
                ! empty($data['deal_id']) => Deal::whereKey($data['deal_id'])->value('contact_id'),
                ! empty($data['ticket_id']) => Ticket::whereKey($data['ticket_id'])->value('contact_id'),
                default => null,
            };
        }

        return $data;
    }

    protected function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->with([
                'responsible:id,name',
                'deal:deal_id,title',
                'ticket:ticket_id,subject',
                'contact:contact_id,first_name,last_name',
            ])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereLike('description', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            // Open tasks first, soonest due first, undated last.
            ->orderByRaw('case when status = ? then 1 else 0 end', [TaskStatus::Done->value])
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date');
    }
}
