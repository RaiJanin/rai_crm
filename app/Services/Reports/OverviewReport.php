<?php

namespace App\Services\Reports;

use App\Enums\DealStage;
use App\Enums\Page;
use App\Enums\PrintView;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

/**
 * Business-wide report: pipeline, outcomes, tasks, support and accounts.
 */
class OverviewReport extends Report
{
    /**
     * Months of won/lost history shown.
     */
    public const MONTHS = 6;

    /**
     * Support metrics cover tickets opened this many days back.
     */
    public const SUPPORT_DAYS = 90;

    public function page(): Page
    {
        return Page::ReportsIndex;
    }

    public function printView(): PrintView
    {
        return PrintView::Overview;
    }

    public function data(): array
    {
        $byStage = $this->byStage();
        $won = collect($byStage)->firstWhere('stage', DealStage::Won->value)['count'] ?? 0;
        $lost = collect($byStage)->firstWhere('stage', DealStage::Lost->value)['count'] ?? 0;

        return [
            'byStage' => $byStage,
            'monthly' => $this->monthlyOutcomes(),
            'byTaskStatus' => $this->byTaskStatus(),
            'topCompanies' => $this->topCompanies(),
            'winRate' => ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : null,
            'support' => (new SupportMetrics(
                Ticket::where('created_at', '>=', now()->subDays(self::SUPPORT_DAYS))->get()->toBase(),
            ))->toArray(),
            'companies' => $this->companies(),
            'generatedAt' => $this->generatedAt,
        ];
    }

    /**
     * The page also lists customers for their individual reports.
     */
    public function pageProps(): array
    {
        return [...$this->data(), 'customers' => $this->customers()];
    }

    /**
     * @return list<array{stage: string, label: string, count: int, value: float}>
     */
    private function byStage(): array
    {
        $totals = Deal::query()
            ->selectRaw('stage, count(*) as total, coalesce(sum(value), 0) as value')
            ->groupBy('stage')
            ->get()
            ->keyBy(fn (Deal $deal) => $deal->stage->value);

        return array_map(fn (DealStage $stage) => [
            'stage' => $stage->value,
            'label' => $stage->label(),
            'count' => (int) ($totals[$stage->value]->total ?? 0),
            'value' => (float) ($totals[$stage->value]->value ?? 0),
        ], DealStage::cases());
    }

    /**
     * @return list<array{status: string, label: string, count: int}>
     */
    private function byTaskStatus(): array
    {
        $totals = Task::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return array_map(fn (TaskStatus $status) => [
            'status' => $status->value,
            'label' => $status->label(),
            'count' => (int) ($totals[$status->value] ?? 0),
        ], TaskStatus::cases());
    }

    /**
     * Won and lost deals per month, oldest first.
     *
     * @return list<array{month: string, won: int, wonValue: float, lost: int}>
     */
    private function monthlyOutcomes(): array
    {
        $start = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $closed = Deal::query()
            ->whereIn('stage', [DealStage::Won, DealStage::Lost])
            ->where('closed_at', '>=', $start)
            ->get(['stage', 'value', 'closed_at'])
            ->groupBy(fn (Deal $deal) => $deal->closed_at?->format('Y-m') ?? 'undated');

        $months = [];

        for ($month = $start; $month <= now(); $month = $month->addMonth()) {
            $deals = $closed->get($month->format('Y-m'), new Collection);
            $wonDeals = $deals->where('stage', DealStage::Won);

            $months[] = [
                'month' => $month->format('M Y'),
                'won' => $wonDeals->count(),
                'wonValue' => (float) $wonDeals->sum('value'),
                'lost' => $deals->where('stage', DealStage::Lost)->count(),
            ];
        }

        return $months;
    }

    /**
     * Companies ranked by total won deal value.
     *
     * @return list<array{company_id: int, hash_id: string|null, name: string, value: float, deals: int}>
     */
    private function topCompanies(int $limit = 5): array
    {
        $totals = Deal::query()
            ->where('stage', DealStage::Won)
            ->whereNotNull('company_id')
            ->selectRaw('company_id, count(*) as won_count, sum(value) as value')
            ->groupBy('company_id')
            ->orderByDesc('value')
            ->limit($limit)
            ->get();

        $companies = Company::whereIn('company_id', $totals->pluck('company_id'))->get()->keyBy('company_id');

        $ranked = [];

        foreach ($totals as $row) {
            $company = $companies->get($row->company_id);

            if ($company === null) {
                continue;
            }

            $ranked[] = [
                'company_id' => $company->company_id,
                'hash_id' => $company->hash_id,
                'name' => $company->name,
                'value' => (float) $row->value,
                'deals' => (int) $row->getAttribute('won_count'),
            ];
        }

        return $ranked;
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies(): Collection
    {
        return Company::query()
            ->select(['company_id', 'name', 'industry'])
            ->withReportTotals()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Contact>
     */
    private function customers(): Collection
    {
        return Contact::query()
            ->select(['contact_id', 'first_name', 'last_name', 'title', 'company_id'])
            ->with('company:company_id,name')
            ->withReportTotals()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
