<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Page;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ContactRequest;
use App\Models\Contact;
use App\Services\Crm\ContactService;
use App\Services\Crm\LookupService;
use App\Services\Crm\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contacts,
        private readonly LookupService $lookups,
    ) {}

    public function index(Request $request): Response
    {
        $filters = ['search' => $request->string('search')->trim()->value()];

        return $this->inertia(Page::ContactsIndex, [
            'contacts' => $this->contacts->paginate($filters),
            'companies' => $this->lookups->companies(),
            'filters' => $filters,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $contact = $this->contacts->create($request->validated(), $request->user());

        $this->toast('Contact created.');

        return to_route('contacts.show', $contact);
    }

    public function show(Contact $contact, TicketService $tickets): Response
    {
        return $this->inertia(Page::ContactsShow, [
            'contact' => $this->contacts->loadProfile($contact),
            'tickets' => $tickets->recent($contact->tickets()),
            'companies' => $this->lookups->companies(),
            'users' => $this->lookups->users(),
            'taskStatuses' => TaskStatus::options(),
        ]);
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->contacts->update($contact, $request->validated());

        $this->toast('Contact updated.');

        return back();
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->contacts->trash($contact);

        $this->toast('Contact and related deals moved to trash.');

        return to_route('contacts.index');
    }
}
