<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ReportPeriodRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Services\Reports\AccountReport;
use App\Services\Reports\OverviewReport;
use App\Services\Reports\Report;
use Illuminate\View\View;
use Inertia\Response;

/**
 * Shows reports as app pages and as print templates. Each {@see Report}
 * knows its own page, print view and data.
 */
class ReportController extends Controller
{
    public function index(): Response
    {
        return $this->show(new OverviewReport);
    }

    public function printOverview(): View
    {
        return $this->print(new OverviewReport);
    }

    public function company(ReportPeriodRequest $request, Company $company): Response
    {
        return $this->show(new AccountReport($company, $request->period()));
    }

    public function contact(ReportPeriodRequest $request, Contact $contact): Response
    {
        return $this->show(new AccountReport($contact, $request->period()));
    }

    public function printCompany(ReportPeriodRequest $request, Company $company): View
    {
        return $this->print(new AccountReport($company, $request->period()));
    }

    public function printContact(ReportPeriodRequest $request, Contact $contact): View
    {
        return $this->print(new AccountReport($contact, $request->period()));
    }

    private function show(Report $report): Response
    {
        return $this->inertia($report->page(), $report->pageProps());
    }

    private function print(Report $report): View
    {
        return view($report->printView()->view(), $report->data());
    }
}
