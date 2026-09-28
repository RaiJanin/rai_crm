<?php

namespace App\Services\Crm;

use App\Enums\TicketStatus;
use App\Enums\TicketView;
use App\Models\Contact;
use App\Models\CrmRecord;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends RecordService<Ticket>
 */
class TicketService extends RecordService
{
    protected function model(): string
    {
        return Ticket::class;
    }

    protected function perPage(): int
    {
        return 20;
    }

    /**
     * New tickets start as "new"; a ticket defaults to its contact's company.
     */
    protected function prepare(array $data, ?CrmRecord $record = null): array
    {
        if ($record === null) {
            $data['status'] = TicketStatus::New;
        }

        if (empty($data['company_id'])) {
            $data['company_id'] = Contact::whereKey($data['contact_id'])->value('company_id');
        }

        return $data;
    }

    protected function filter(Builder $query, array $filters): Builder
    {
        $status = $filters['status'] ?? TicketView::DEFAULT->value;
        $view = TicketView::tryFrom($status);

        return $query
            ->with(['contact:contact_id,first_name,last_name', 'company:company_id,name', 'assignee:id,name'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->whereLike('subject', "%{$search}%")
                ->orWhereLike('description', "%{$search}%")))
            ->when($view, fn ($query, TicketView $view) => $view->apply($query))
            ->when($view === null, fn ($query) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'medium' then 2 else 3 end")
            ->orderBy('resolution_due_at');
    }

    /**
     * The ticket queue: filtered, paginated tickets with their SLA state.
     *
     * @param  array<string, mixed>  $filters
     * @param  Builder<Ticket>|null  $scope
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function queue(array $filters, ?Builder $scope = null): LengthAwarePaginator
    {
        return $this->paginate($filters, $scope)->through(fn (Ticket $ticket) => $this->withSla($ticket));
    }

    /**
     * Queue counters for the list header, within the given scope.
     *
     * @param  Builder<Ticket>|null  $scope
     * @return array{active: int, breached: int, unassigned: int}
     */
    public function counts(?Builder $scope = null): array
    {
        $scope ??= Ticket::query();

        return [
            TicketView::Active->value => TicketView::Active->apply(clone $scope)->count(),
            TicketView::Breached->value => TicketView::Breached->apply(clone $scope)->count(),
            'unassigned' => Ticket::active()->whereNull('assignee_id')->count(),
        ];
    }

    /**
     * Apply a partial update from the ticket sidebar and describe what changed.
     *
     * @param  array<string, mixed>  $changes
     */
    public function triage(Ticket $ticket, array $changes): string
    {
        $ticket->update($changes);

        return match (true) {
            array_key_exists('satisfaction', $changes) || array_key_exists('resolution', $changes) => "Ticket {$ticket->reference} resolved.",
            array_key_exists('status', $changes) => "Status changed to {$ticket->status->label()}.",
            default => 'Ticket updated.',
        };
    }

    /**
     * Latest tickets of a contact, company or deal, each with its SLA state.
     *
     * @param  Relation<Ticket, *, *>  $tickets
     * @return array<int, array<string, mixed>>
     */
    public function recent(Relation $tickets, int $limit = 20): array
    {
        return $tickets->with(['assignee:id,name', 'contact:contact_id,first_name,last_name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Ticket $ticket) => $this->withSla($ticket))
            ->values()
            ->all();
    }

    /**
     * Other tickets from the same customer.
     *
     * @return Collection<int, Ticket>
     */
    public function history(Ticket $ticket, int $limit = 5): Collection
    {
        return Ticket::where('contact_id', $ticket->contact_id)
            ->whereKeyNot($ticket->ticket_id)
            ->latest()
            ->limit($limit)
            ->get(['ticket_id', 'subject', 'status', 'created_at']);
    }

    /**
     * Load everything the ticket page shows.
     */
    public function loadProfile(Ticket $ticket): Ticket
    {
        return $ticket->load([
            'contact:contact_id,first_name,last_name,email,phone,title,company_id',
            'company:company_id,name',
            'deal:deal_id,title,stage',
            'assignee:id,name',
            'creator:id,name',
            'tasks' => fn ($query) => $query->with('responsible:id,name')->orderBy('due_date'),
            'comments.user:id,name',
            'attachments.uploader:id,name',
        ]);
    }

    /**
     * Serialise a ticket with its computed SLA state.
     *
     * @return array<string, mixed>
     */
    public function withSla(Ticket $ticket): array
    {
        return [...$ticket->toArray(), 'sla' => $ticket->sla()];
    }
}
