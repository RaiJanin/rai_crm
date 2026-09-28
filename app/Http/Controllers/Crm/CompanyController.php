<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Page;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CompanyRequest;
use App\Models\Company;
use App\Services\Crm\CompanyService;
use App\Services\Crm\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $companies) {}

    public function index(Request $request): Response
    {
        $filters = ['search' => $request->string('search')->trim()->value()];

        return $this->inertia(Page::CompaniesIndex, [
            'companies' => $this->companies->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $company = $this->companies->create($request->validated(), $request->user());

        $this->toast('Company created.');

        return to_route('companies.show', $company);
    }

    public function show(Company $company, TicketService $tickets): Response
    {
        return $this->inertia(Page::CompaniesShow, [
            'company' => $this->companies->loadProfile($company),
            'tickets' => $tickets->recent($company->tickets()),
        ]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->companies->update($company, $request->validated());

        $this->toast('Company updated.');

        return back();
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->companies->trash($company);

        $this->toast('Company moved to trash.');

        return to_route('companies.index');
    }
}
