@php
    use App\Enums\DealStage;
    use App\Support\ReportFormat as F;

    $stageFills = [
        'lead' => 'fill-gray',
        'contacted' => 'fill-blue',
        'qualified' => 'fill-blue',
        'proposal' => 'fill-dark',
        'won' => 'fill-green',
        'lost' => 'fill-red',
    ];
    $statusFills = ['pending' => 'fill-amber', 'in_progress' => 'fill-blue', 'done' => 'fill-green'];

    $maxStage = max(1, collect($byStage)->max('value'));
    $totalTasks = max(1, collect($byTaskStatus)->sum('count'));
    $wonSixMonths = collect($monthly)->sum('wonValue');
    $lostSixMonths = collect($monthly)->sum('lost');
    $pipeline = collect($byStage)->reject(fn ($row) => in_array($row['stage'], ['won', 'lost'], true))->sum('value');
@endphp

<x-print.layout
    report-type="Business overview report"
    title="Business overview"
    subtitle="Sales pipeline, outcomes and customer support"
    :period="'As of '.F::date($generatedAt)"
    :generated-at="$generatedAt"
>
    <div class="kpis">
        <x-print.kpi label="Open pipeline" :value="F::money($pipeline)" hint="All open stages" />
        <x-print.kpi label="Won, last 6 months" :value="F::money($wonSixMonths)" />
        <x-print.kpi label="Lost, last 6 months" :value="$lostSixMonths.' deals'" />
        <x-print.kpi label="Win rate" :value="F::percent($winRate)" hint="Won ÷ (won + lost), all time" />
    </div>

    <div class="columns">
        <x-print.section title="Value by stage" description="All deals">
            <table class="data">
                <thead><tr><th>Stage</th><th class="num">Deals</th><th class="num">Value</th><th style="width: 30%"></th></tr></thead>
                <tbody>
                    @foreach ($byStage as $row)
                        <tr>
                            <td><x-print.badge :tone="F::stageTone(DealStage::from($row['stage']))">{{ $row['label'] }}</x-print.badge></td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="num">{{ F::money($row['value']) }}</td>
                            <td><div class="bar"><span class="{{ $stageFills[$row['stage']] }}" style="width: {{ round($row['value'] / $maxStage * 100) }}%"></span></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-print.section>

        <x-print.section title="Won vs lost" description="Deals closed per month">
            <table class="data">
                <thead><tr><th>Month</th><th class="num">Won</th><th class="num">Won value</th><th class="num">Lost</th></tr></thead>
                <tbody>
                    @foreach ($monthly as $row)
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td class="num">{{ $row['won'] }}</td>
                            <td class="num">{{ F::money($row['wonValue']) }}</td>
                            <td class="num">{{ $row['lost'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="num">{{ collect($monthly)->sum('won') }}</td>
                        <td class="num">{{ F::money($wonSixMonths) }}</td>
                        <td class="num">{{ $lostSixMonths }}</td>
                    </tr>
                </tfoot>
            </table>
        </x-print.section>
    </div>

    <div class="columns">
        <x-print.section title="Top companies" description="By total won value">
            @if (empty($topCompanies))
                <div class="empty">No won deals with a company yet.</div>
            @else
                <table class="data">
                    <thead><tr><th>#</th><th>Company</th><th class="num">Won</th><th class="num">Value</th></tr></thead>
                    <tbody>
                        @foreach ($topCompanies as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $row['name'] }}</strong></td>
                                <td class="num">{{ $row['deals'] }}</td>
                                <td class="num">{{ F::money($row['value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-print.section>

        <x-print.section title="Tasks by status">
            <table class="data">
                <thead><tr><th>Status</th><th class="num">Tasks</th><th style="width: 45%"></th></tr></thead>
                <tbody>
                    @foreach ($byTaskStatus as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td><div class="bar"><span class="{{ $statusFills[$row['status']] }}" style="width: {{ round($row['count'] / $totalTasks * 100) }}%"></span></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-print.section>
    </div>

    <x-print.section title="Customer support" :description="'Tickets opened in the last 90 days ('.$support['total'].')'">
        <div class="kpis">
            <x-print.kpi label="Avg. first response" :value="F::duration($support['avgFirstResponseHours'])" />
            <x-print.kpi label="Avg. resolution" :value="F::duration($support['avgResolutionHours'])" />
            <x-print.kpi label="SLA compliance" :value="F::percent($support['slaCompliance'])" hint="Resolved within target" />
            <x-print.kpi
                label="Customer satisfaction"
                :value="$support['csat'] ? $support['csat']['average'].' / 5' : '—'"
                :hint="$support['csat'] ? $support['csat']['satisfied'].'% satisfied' : 'No ratings'"
            />
        </div>

        @if ($support['total'] > 0)
            <div class="keep" style="margin-top: 12px;">
                <p class="sub" style="margin: 0 0 6px;">Ticket breakdown · opened in the last 90 days</p>
                <div class="columns" style="grid-template-columns: repeat(3, 1fr); gap: 14px;">
                @foreach (['byStatus' => 'By status', 'byPriority' => 'By priority', 'byType' => 'By type'] as $key => $label)
                    <table class="data">
                        <thead><tr><th>{{ $label }}</th><th class="num">Tickets</th></tr></thead>
                        <tbody>
                            @foreach ($support[$key] as $row)
                                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
                </div>
            </div>
        @endif
    </x-print.section>

    <x-print.section title="Accounts" description="Pipeline, revenue won in the last 12 months and support load per company">
        @if ($companies->isEmpty())
            <div class="empty">No companies yet.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th class="num">Open deals</th>
                        <th class="num">Open pipeline</th>
                        <th class="num">Won (12 mo)</th>
                        <th class="num">Open tickets</th>
                        <th class="num">CSAT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td><strong>{{ $company->name }}</strong><span class="sub">{{ $company->industry ?? '—' }}</span></td>
                            <td class="num">{{ $company->open_deals_count }}</td>
                            <td class="num">{{ F::money($company->pipeline_value) }}</td>
                            <td class="num">{{ F::money($company->won_value) }}</td>
                            <td class="num">{{ $company->open_tickets_count }}</td>
                            <td class="num">{{ $company->csat_average ? number_format((float) $company->csat_average, 1).'/5' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="num">{{ $companies->sum('open_deals_count') }}</td>
                        <td class="num">{{ F::money($companies->sum('pipeline_value')) }}</td>
                        <td class="num">{{ F::money($companies->sum('won_value')) }}</td>
                        <td class="num">{{ $companies->sum('open_tickets_count') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </x-print.section>
</x-print.layout>
