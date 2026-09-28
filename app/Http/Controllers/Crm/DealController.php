<?php

namespace App\Http\Controllers\Crm;

use App\Enums\DealStage;
use App\Enums\Page;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\DealRequest;
use App\Http\Requests\Crm\UpdateDealStageRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Services\Crm\DealService;
use App\Services\Crm\LookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class DealController extends Controller
{
    public function __construct(
        private readonly DealService $deals,
        private readonly LookupService $lookups,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', Rule::enum(DealStage::class)],
        ]);

        return $this->inertia(Page::DealsIndex, [
            'deals' => $this->deals->paginate($filters),
            'stages' => DealStage::options(),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'stage' => $filters['stage'] ?? '',
            ],
        ]);
    }

    public function pipeline(): Response
    {
        return $this->inertia(Page::DealsPipeline, [
            'stages' => DealStage::options(),
            'deals' => $this->deals->pipeline(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->inertia(Page::DealsCreate, [
            ...$this->lookups->dealForm(),
            'defaults' => [
                'contact_id' => Contact::decodeHashId($request->query('contact')),
                'company_id' => Company::decodeHashId($request->query('company')),
            ],
        ]);
    }

    public function store(DealRequest $request): RedirectResponse
    {
        $deal = $this->deals->create($request->validated(), $request->user());

        $this->toast('Deal created.');

        return to_route('deals.show', $deal);
    }

    public function show(Deal $deal): Response
    {
        return $this->inertia(Page::DealsShow, [
            'deal' => $this->deals->loadProfile($deal),
            ...$this->lookups->dealForm(),
            'users' => $this->lookups->users(),
            'taskStatuses' => TaskStatus::options(),
        ]);
    }

    public function update(DealRequest $request, Deal $deal): RedirectResponse
    {
        $this->deals->update($deal, $request->validated());

        $this->toast('Deal updated.');

        return back();
    }

    public function updateStage(UpdateDealStageRequest $request, Deal $deal): RedirectResponse
    {
        $this->deals->moveToStage($deal, $request->enum('stage', DealStage::class));

        return back();
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $this->deals->trash($deal);

        $this->toast('Deal moved to trash.');

        return to_route('deals.index');
    }
}
