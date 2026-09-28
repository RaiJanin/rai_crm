<?php

namespace App\Services\Crm;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends RecordService<Company>
 */
class CompanyService extends RecordService
{
    protected function model(): string
    {
        return Company::class;
    }

    protected function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->withCount(['contacts', 'deals'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereLike('name', "%{$search}%"))
            ->orderBy('name');
    }

    /**
     * Load everything the company page shows.
     */
    public function loadProfile(Company $company): Company
    {
        return $company->load([
            'contacts' => fn ($query) => $query->orderBy('first_name'),
            'deals' => fn ($query) => $query->with('contact:contact_id,first_name,last_name')->latest(),
            'comments.user:id,name',
            'attachments.uploader:id,name',
        ]);
    }
}
