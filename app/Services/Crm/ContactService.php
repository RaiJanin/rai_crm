<?php

namespace App\Services\Crm;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends RecordService<Contact>
 */
class ContactService extends RecordService
{
    protected function model(): string
    {
        return Contact::class;
    }

    protected function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->with('company:company_id,name')
            ->withCount('deals')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->whereLike('first_name', "%{$search}%")
                ->orWhereLike('last_name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")))
            ->orderBy('first_name')
            ->orderBy('last_name');
    }

    /**
     * Load everything the contact page shows.
     */
    public function loadProfile(Contact $contact): Contact
    {
        return $contact->load([
            'company:company_id,name',
            'deals' => fn ($query) => $query->latest(),
            'tasks' => fn ($query) => $query
                ->with(['responsible:id,name', 'deal:deal_id,title', 'ticket:ticket_id,subject'])
                ->orderBy('due_date'),
            'comments.user:id,name',
            'attachments.uploader:id,name',
        ]);
    }
}
