<?php

namespace App\Services\Reports;

use App\Enums\DealStage;
use App\Enums\Page;
use App\Enums\PrintView;
use App\Enums\SlaState;
use App\Enums\TicketStatus;
use App\Models\Account;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

/**
 * Sales and support report for one account — company or customer contact.
 *
 * Everything that differs between the two (contacts listed, whose tasks
 * count as follow-ups, subtitle) comes from the {@see Account} subclass.
 */
class AccountReport extends Report
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    /** @var Collection<int, Ticket> */
    private Collection $loadedTickets;

    public function __construct(
        private readonly Account $account,
        private readonly ReportPeriod $period,
    ) {
        parent::__construct();
    }

    public function page(): Page
    {
        return Page::ReportsAccount;
    }

    public function printView(): PrintView
    {
        return PrintView::Account;
    }

    public function data(): array
    {
        return $this->data ??= $this->build();
    }

    /**
     * The page needs each ticket's SLA state serialised alongside it.
     */
    public function pageProps(): array
    {
        $data = $this->data();
        $data['tickets'] = $this->loadedTickets->map(fn (Ticket $ticket) => [...$ticket->toArray(), 'sla' => $ticket->sla()])->values();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $deals = $this->deals();
        $tickets = $this->loadedTickets = $this->tickets();

        $won = $deals->where('stage', DealStage::Won);
        $lost = $deals->where('stage', DealStage::Lost);
        $open = $deals->filter(fn (Deal $deal) => ! $deal->stage->isClosed());
        $decided = $won->count() + $lost->count();

        return [
            'kind' => $this->account->reportKind(),
            'account' => $this->account->prepareForReport(),
            'contacts' => $this->account->reportContacts(),
            'period' => $this->period->value,
            'periods' => ReportPeriod::options(),
            'since' => $this->period->since,
            'generatedAt' => $this->generatedAt,
            'summary' => [
                'wonValue' => (float) $won->sum('value'),
                'wonCount' => $won->count(),
                'lostCount' => $lost->count(),
                'pipelineValue' => (float) $open->sum('value'),
                'openDeals' => $open->count(),
                'winRate' => $decided > 0 ? round($won->count() / $decided * 100, 1) : null,
                'openTickets' => $tickets->filter(fn (Ticket $ticket) => $ticket->status->isActive())->count(),
                'breachedTickets' => $tickets->filter(fn (Ticket $ticket) => $ticket->sla()['state'] === SlaState::Breached)->count(),
            ],
            'deals' => $deals,
            'tickets' => $tickets,
            'support' => (new SupportMetrics(
                $tickets->filter(fn (Ticket $ticket) => $this->period->includes($ticket->created_at))->values()->toBase(),
            ))->toArray(),
            'followUps' => $this->followUps($deals, $tickets),
        ];
    }

    /**
     * Open deals are a current snapshot; closed deals are limited to the period.
     *
     * @return Collection<int, Deal>
     */
    private function deals(): Collection
    {
        $since = $this->period->since;

        return $this->account->deals()
            ->with('contact:contact_id,first_name,last_name')
            ->where(fn ($query) => $query
                ->open()
                ->orWhere(fn ($query) => $query
                    ->whereIn('stage', [DealStage::Won, DealStage::Lost])
                    ->when($since, fn ($query) => $query->where('closed_at', '>=', $since))))
            ->orderByRaw("case stage when 'won' then 1 when 'lost' then 2 else 0 end")
            ->orderByDesc('value')
            ->get();
    }

    /**
     * Tickets opened in the period, plus anything still active from before it.
     *
     * @return Collection<int, Ticket>
     */
    private function tickets(): Collection
    {
        $since = $this->period->since;

        return $this->account->tickets()
            ->with(['contact:contact_id,first_name,last_name', 'assignee:id,name'])
            ->where(fn ($query) => $query
                ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
                ->orWhereIn('status', TicketStatus::active()))
            ->latest()
            ->get();
    }

    /**
     * Pending tasks tied to the account's people, deals or tickets.
     *
     * @param  Collection<int, Deal>  $deals
     * @param  Collection<int, Ticket>  $tickets
     * @return Collection<int, Task>
     */
    private function followUps(Collection $deals, Collection $tickets): Collection
    {
        return Task::pending()
            ->where(fn ($query) => $query
                ->whereIn('contact_id', $this->account->followUpContactIds())
                ->orWhereIn('deal_id', $deals->modelKeys())
                ->orWhereIn('ticket_id', $tickets->modelKeys()))
            ->with(['responsible:id,name', 'deal:deal_id,title', 'ticket:ticket_id,subject'])
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->get();
    }
}
