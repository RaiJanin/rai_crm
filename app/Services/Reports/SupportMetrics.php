<?php

namespace App\Services\Reports;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Ticket;
use App\Contracts\HasLabel;
use Illuminate\Support\Collection;

/**
 * Help desk metrics for a set of tickets: volumes, response times, SLA and CSAT.
 */
final class SupportMetrics
{
    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    public function __construct(private readonly Collection $tickets) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $responded = $this->tickets->whereNotNull('first_responded_at');
        $resolved = $this->tickets->whereNotNull('resolved_at');
        $rated = $this->tickets->whereNotNull('satisfaction');

        return [
            'total' => $this->tickets->count(),
            'byStatus' => $this->breakdown('status', TicketStatus::cases()),
            'byPriority' => $this->breakdown('priority', TicketPriority::cases()),
            'byType' => $this->breakdown('type', TicketType::cases()),
            'avgFirstResponseHours' => $responded->isEmpty() ? null
                : round($responded->avg(fn (Ticket $ticket) => $this->hoursUntil($ticket, 'first_responded_at')), 1),
            'avgResolutionHours' => $resolved->isEmpty() ? null
                : round($resolved->avg(fn (Ticket $ticket) => $this->hoursUntil($ticket, 'resolved_at')), 1),
            'slaCompliance' => $resolved->isEmpty() ? null
                : round($resolved->filter(fn (Ticket $ticket) => $ticket->resolved_at <= $ticket->resolution_due_at)->count()
                    / $resolved->count() * 100, 1),
            'csat' => $rated->isEmpty() ? null : [
                'average' => round($rated->avg('satisfaction'), 1),
                'satisfied' => round($rated->where('satisfaction', '>=', 4)->count() / $rated->count() * 100),
                'responses' => $rated->count(),
            ],
        ];
    }

    /**
     * @param  list<TicketStatus|TicketPriority|TicketType>  $cases
     */
    private function breakdown(string $field, array $cases): array
    {
        return array_map(fn (HasLabel $case) => [
            'value' => (string) $case->value,
            'label' => $case->label(),
            'count' => $this->tickets->filter(fn (Ticket $ticket) => $ticket->{$field} === $case)->count(),
        ], $cases);
    }

    private function hoursUntil(Ticket $ticket, string $until): float
    {
        return $ticket->created_at->diffInMinutes($ticket->{$until}) / 60;
    }
}
