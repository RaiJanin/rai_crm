<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ListScope;
use App\Enums\Page;
use App\Enums\TaskStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\TicketRequest;
use App\Http\Requests\Crm\TriageTicketRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Ticket;
use App\Services\Crm\LookupService;
use App\Services\Crm\TicketService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $tickets,
        private readonly LookupService $lookups,
    ) {}

    public function allTickets(Request $request): Response
    {
        return $this->render($request, ListScope::All);
    }

    public function myTickets(Request $request): Response
    {
        return $this->render($request, ListScope::Mine, Ticket::where('assignee_id', $request->user()->id));
    }

    public function create(Request $request): Response
    {
        return $this->inertia(Page::TicketsCreate, [
            ...$this->lookups->ticketForm(),
            'defaults' => [
                'contact_id' => Contact::decodeHashId($request->query('contact')),
                'company_id' => Company::decodeHashId($request->query('company')),
                'deal_id' => Deal::decodeHashId($request->query('deal')),
            ],
        ]);
    }

    public function store(TicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create($request->validated(), $request->user());

        $this->toast("Ticket {$ticket->reference} created.");

        return to_route('tickets.show', $ticket);
    }

    public function show(Ticket $ticket): Response
    {
        return $this->inertia(Page::TicketsShow, [
            'ticket' => $this->tickets->withSla($this->tickets->loadProfile($ticket)),
            'history' => $this->tickets->history($ticket),
            ...$this->lookups->ticketForm(),
            'taskStatuses' => TaskStatus::options(),
        ]);
    }

    public function update(TicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->update($ticket, $request->validated());

        $this->toast('Ticket updated.');

        return back();
    }

    public function triage(TriageTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->toast($this->tickets->triage($ticket, $request->validated()));

        return back();
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->tickets->trash($ticket);

        $this->toast("Ticket {$ticket->reference} moved to trash.");

        return to_route('tickets.all-tickets');
    }

    /**
     * @param  Builder<Ticket>|null  $query
     */
    private function render(Request $request, ListScope $scope, ?Builder $query = null): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(TicketView::filterValues())],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
        ]);

        return $this->inertia(Page::TicketsIndex, [
            'scope' => $scope,
            'tickets' => $this->tickets->queue($filters, $query ? clone $query : null),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? TicketView::DEFAULT->value,
                'priority' => $filters['priority'] ?? '',
            ],
            'counts' => $this->tickets->counts($query),
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
        ]);
    }
}
