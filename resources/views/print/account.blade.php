@php
    use App\Support\ReportFormat as F;

    /** @var \App\Models\Company|\App\Models\Contact $account */
    $isCompany = $kind === \App\Enums\AccountKind::Company;
    $name = $isCompany ? $account->name : $account->full_name;
    $subtitle = $isCompany
        ? $account->industry
        : collect([$account->title, $account->company?->name])->filter()->implode(' · ');

    $periodLabel = collect($periods)->firstWhere('value', $period)['label'] ?? '';
    $range = $since
        ? F::date($since).' – '.F::date($generatedAt)
        : 'All time to '.F::date($generatedAt);

    $plural = fn (int $count, string $word) => $count.' '.$word.($count === 1 ? '' : 's');
@endphp

<x-print.layout
    :report-type="$kind->reportTitle()"
    :title="$name"
    :subtitle="$subtitle"
    :period="$periodLabel.' · '.$range"
    :generated-at="$generatedAt"
>
    {{-- Summary --}}
    <div class="kpis">
        <x-print.kpi label="Won revenue" :value="F::money($summary['wonValue'])" :hint="$plural($summary['wonCount'], 'deal').' won'" />
        <x-print.kpi label="Open pipeline" :value="F::money($summary['pipelineValue'])" :hint="$plural($summary['openDeals'], 'open deal')" />
        <x-print.kpi label="Win rate" :value="F::percent($summary['winRate'])" :hint="$summary['wonCount'].' won · '.$summary['lostCount'].' lost'" />
        <x-print.kpi
            label="Open tickets"
            :value="$summary['openTickets']"
            :hint="$summary['breachedTickets'] ? $summary['breachedTickets'].' past SLA' : 'All within SLA'"
        />
        <x-print.kpi label="Avg. first response" :value="F::duration($support['avgFirstResponseHours'])" />
        <x-print.kpi label="Avg. resolution" :value="F::duration($support['avgResolutionHours'])" />
        <x-print.kpi label="SLA compliance" :value="F::percent($support['slaCompliance'])" hint="Resolved within target" />
        <x-print.kpi
            label="Customer satisfaction"
            :value="$support['csat'] ? $support['csat']['average'].' / 5' : '—'"
            :hint="$support['csat'] ? $plural($support['csat']['responses'], 'rating') : 'No ratings'"
        />
    </div>

    {{-- Deals --}}
    <x-print.section title="Deals" description="Open deals, plus deals won or lost in the period">
        @if ($deals->isEmpty())
            <div class="empty">No deals in this period.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Deal</th>
                        @if ($isCompany)<th>Contact</th>@endif
                        <th>Stage</th>
                        <th class="num">Value</th>
                        <th>Expected / closed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deals as $deal)
                        <tr>
                            <td><strong>{{ $deal->title }}</strong></td>
                            @if ($isCompany)<td>{{ $deal->contact?->full_name }}</td>@endif
                            <td><x-print.badge :tone="F::stageTone($deal->stage)">{{ $deal->stage->label() }}</x-print.badge></td>
                            <td class="num">{{ F::money($deal->value) }}</td>
                            <td class="nowrap">{{ $deal->closed_at ? F::date($deal->closed_at) : F::day($deal->expected_close_date) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="{{ $isCompany ? 3 : 2 }}">Open pipeline · Won in period</td>
                        <td class="num">{{ F::money($summary['pipelineValue']) }} · {{ F::money($summary['wonValue']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </x-print.section>

    {{-- Support --}}
    <x-print.section title="Support tickets" description="Opened in the period, plus any still active">
        @if ($tickets->isEmpty())
            <div class="empty">No support tickets in this period.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        @if ($isCompany)<th>Contact</th>@endif
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Opened</th>
                        <th>Resolved</th>
                        <th>SLA</th>
                        <th class="num">CSAT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        @php([$slaTone, $slaLabel] = F::sla($ticket->sla()['state']))
                        <tr>
                            <td>
                                <strong>{{ $ticket->subject }}</strong>
                                <span class="sub">{{ $ticket->reference }} · {{ $ticket->type->label() }}</span>
                            </td>
                            @if ($isCompany)<td>{{ $ticket->contact?->full_name }}</td>@endif
                            <td><x-print.badge :tone="F::priorityTone($ticket->priority)">{{ $ticket->priority->label() }}</x-print.badge></td>
                            <td><x-print.badge :tone="F::ticketStatusTone($ticket->status)">{{ $ticket->status->label() }}</x-print.badge></td>
                            <td class="nowrap">{{ F::date($ticket->created_at) }}</td>
                            <td class="nowrap">{{ F::date($ticket->resolved_at) }}</td>
                            <td><x-print.badge :tone="$slaTone">{{ $slaLabel }}</x-print.badge></td>
                            <td class="num">{{ $ticket->satisfaction ? $ticket->satisfaction.'/5' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-print.section>

    {{-- People --}}
    @if ($isCompany)
        <x-print.section title="Contacts">
            @if ($contacts->isEmpty())
                <div class="empty">No contacts on file.</div>
            @else
                <table class="data">
                    <thead>
                        <tr><th>Name</th><th>Title</th><th>Email</th><th>Phone</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($contacts as $person)
                            <tr>
                                <td><strong>{{ $person->full_name }}</strong></td>
                                <td>{{ $person->title ?? '—' }}</td>
                                <td>{{ $person->email ?? '—' }}</td>
                                <td>{{ $person->phone ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-print.section>
    @else
        <x-print.section title="Contact details">
            <dl class="details">
                <div><dt>Email</dt><dd>{{ $account->email ?? '—' }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $account->phone ?? '—' }}</dd></div>
                <div><dt>Company</dt><dd>{{ $account->company?->name ?? '—' }}</dd></div>
                <div><dt>Source</dt><dd>{{ $account->source ?? '—' }}</dd></div>
            </dl>
        </x-print.section>
    @endif

    {{-- Follow-ups --}}
    <x-print.section title="Open follow-ups" description="Pending tasks for this account">
        @if ($followUps->isEmpty())
            <div class="empty">No open follow-ups.</div>
        @else
            <table class="data">
                <thead>
                    <tr><th>Task</th><th>Related to</th><th>Assigned to</th><th>Due</th></tr>
                </thead>
                <tbody>
                    @foreach ($followUps as $task)
                        <tr>
                            <td>{{ $task->description }}</td>
                            <td>{{ $task->deal?->title ?? $task->ticket?->reference ?? '—' }}</td>
                            <td>{{ $task->responsible?->name ?? 'Unassigned' }}</td>
                            <td class="nowrap">{{ F::day($task->due_date) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-print.section>
</x-print.layout>
