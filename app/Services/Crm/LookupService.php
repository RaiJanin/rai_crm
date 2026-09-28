<?php

namespace App\Services\Crm;

use App\Enums\DealStage;
use App\Enums\TaskStatus;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Select-box options shared by forms across the CRM.
 */
class LookupService
{
    /**
     * @return Collection<int, Contact>
     */
    public function contacts(): Collection
    {
        return Contact::orderBy('first_name')->get(['contact_id', 'first_name', 'last_name', 'company_id']);
    }

    /**
     * @return Collection<int, Company>
     */
    public function companies(): Collection
    {
        return Company::orderBy('name')->get(['company_id', 'name']);
    }

    /**
     * @return Collection<int, Deal>
     */
    public function deals(bool $openOnly = false): Collection
    {
        return Deal::query()
            ->when($openOnly, fn ($query) => $query->open())
            ->orderBy('title')
            ->get(['deal_id', 'title', 'contact_id']);
    }

    /**
     * @return Collection<int, User>
     */
    public function users(): Collection
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Options for the deal create/edit form.
     *
     * @return array<string, mixed>
     */
    public function dealForm(): array
    {
        return [
            'contacts' => $this->contacts(),
            'companies' => $this->companies(),
            'stages' => DealStage::options(),
        ];
    }

    /**
     * Options for the task create/edit dialog.
     *
     * @return array<string, mixed>
     */
    public function taskForm(): array
    {
        return [
            'taskStatuses' => TaskStatus::options(),
            'users' => $this->users(),
            'deals' => $this->deals(openOnly: true),
            'contacts' => $this->contacts(),
        ];
    }

    /**
     * Options for the ticket create/edit form and sidebar.
     *
     * @return array<string, mixed>
     */
    public function ticketForm(): array
    {
        return [
            'contacts' => $this->contacts(),
            'companies' => $this->companies(),
            'deals' => $this->deals(),
            'users' => $this->users(),
            'types' => TicketType::options(),
            'priorities' => TicketPriority::options(),
            'statuses' => TicketStatus::options(),
            'channels' => TicketChannel::options(),
        ];
    }
}
