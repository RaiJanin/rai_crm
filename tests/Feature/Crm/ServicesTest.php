<?php

use App\Enums\AccountKind;
use App\Enums\TrashType;
use App\Exceptions\RestoreBlockedException;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Crm\CommentService;
use App\Services\Crm\DashboardService;
use App\Services\Crm\TicketService;
use App\Services\Reports\AccountReport;
use App\Services\Reports\ReportPeriod;

test('account reports work polymorphically for companies and contacts', function () {
    $company = Company::factory()->create(['industry' => 'Retail']);
    $contact = Contact::factory()->for($company)->create(['title' => 'Buyer']);
    Deal::factory()->for($contact)->create(['company_id' => $company->company_id, 'value' => 500]);

    $companyReport = (new AccountReport($company, ReportPeriod::from('90d')))->data();
    $contactReport = (new AccountReport($contact, ReportPeriod::from('90d')))->data();

    expect($companyReport['kind'])->toBe(AccountKind::Company)
        ->and($companyReport['contacts'])->toHaveCount(1)
        ->and($companyReport['summary']['pipelineValue'])->toBe(500.0)
        ->and($contactReport['kind'])->toBe(AccountKind::Contact)
        ->and($contactReport['contacts'])->toHaveCount(0)
        ->and($contactReport['account']->relationLoaded('company'))->toBeTrue()
        ->and($contact->reportSubtitle())->toBe('Buyer · '.$company->name);
});

test('report periods reject unknown values', function () {
    expect(fn () => ReportPeriod::from('7d'))->toThrow(InvalidArgumentException::class)
        ->and(ReportPeriod::from(null)->value)->toBe(ReportPeriod::DEFAULT)
        ->and(ReportPeriod::from('all')->since)->toBeNull();
});

test('a public comment only counts as a customer reply on records that accept one', function () {
    $user = User::factory()->create();
    $deal = Deal::factory()->create();
    $ticket = Ticket::factory()->create();

    $service = app(CommentService::class);

    expect($service->add($deal, $user, 'Hi', isPublic: true)->is_public)->toBeFalse()
        ->and($service->add($ticket, $user, 'Hi', isPublic: true)->is_public)->toBeTrue()
        ->and($ticket->fresh()->first_responded_at)->not->toBeNull();
});

test('ticket service defaults the company and starts tickets as new', function () {
    $contact = Contact::factory()->for(Company::factory())->create();

    $ticket = app(TicketService::class)->create([
        'subject' => 'Printer offline',
        'description' => 'Store 3 printer is offline.',
        'contact_id' => $contact->contact_id,
        'type' => 'incident',
        'priority' => 'high',
        'channel' => 'phone',
    ], User::factory()->create());

    expect($ticket->company_id)->toBe($contact->company_id)
        ->and($ticket->status->value)->toBe('new');
});

test('trash bins resolve by type and block orphaning restores', function () {
    $deal = Deal::factory()->create();
    $deal->contact->delete();

    expect(fn () => TrashType::Deals->bin()->restore($deal->hash_id))->toThrow(RestoreBlockedException::class)
        ->and(TrashType::values())->toBe(['deals', 'contacts', 'companies', 'tickets']);

    TrashType::Contacts->bin()->restore($deal->contact->hash_id);

    expect($deal->fresh()->trashed())->toBeFalse();
});

test('the activity feed describes any record through its label', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();
    app(CommentService::class)->add($ticket, $user, 'Called the customer');

    $item = app(DashboardService::class)->activity()->firstWhere('kind', 'comment');

    expect($item['subject'])->toBe(['type' => 'ticket', 'id' => $ticket->hash_id, 'label' => $ticket->label()]);
});

test('hash ids stay distinct per model after moving to the base class', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->create(['contact_id' => $company->company_id]);

    expect($company->hash_id)->not->toBe($contact->hash_id);
});
