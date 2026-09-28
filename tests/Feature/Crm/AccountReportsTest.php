<?php

use App\Enums\DealStage;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->company = Company::factory()->create();
    $this->ana = Contact::factory()->for($this->company)->create(['first_name' => 'Ana']);
    $this->ben = Contact::factory()->for($this->company)->create(['first_name' => 'Ben']);
});

/**
 * Create a deal closed $daysAgo days ago.
 */
function closedDeal(Contact $contact, DealStage $stage, float $value, int $daysAgo): Deal
{
    $deal = Deal::factory()->for($contact)->create(['company_id' => $contact->company_id, 'value' => $value]);
    $deal->update(['stage' => $stage]);
    $deal->forceFill(['closed_at' => now()->subDays($daysAgo)])->saveQuietly();

    return $deal;
}

test('the company report covers deals, tickets, contacts and follow-ups', function () {
    closedDeal($this->ana, DealStage::Won, 1000, 10);
    closedDeal($this->ben, DealStage::Lost, 500, 20);
    $open = Deal::factory()->for($this->ben)->create(['company_id' => $this->company->company_id, 'value' => 750]);
    Ticket::factory()->create(['contact_id' => $this->ana->contact_id, 'company_id' => $this->company->company_id]);
    Task::factory()->create(['deal_id' => $open->deal_id]);

    $this->get(route('reports.company', $this->company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Account')
            ->where('kind', 'company')
            ->where('period', '12m')
            ->where('summary.wonValue', 1000)
            ->where('summary.pipelineValue', 750)
            ->where('summary.winRate', 50)
            ->where('summary.openTickets', 1)
            ->has('deals', 3)
            ->has('tickets', 1)
            ->has('contacts', 2)
            ->has('followUps', 1)
            ->has('periods', 4));
});

test('the period limits closed deals but keeps open ones', function () {
    closedDeal($this->ana, DealStage::Won, 1000, 10);
    closedDeal($this->ana, DealStage::Won, 4000, 200);
    Deal::factory()->for($this->ana)->create(['company_id' => $this->company->company_id]);

    $this->get(route('reports.company', ['company' => $this->company, 'period' => '90d']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.wonValue', 1000)
            ->has('deals', 2));

    $this->get(route('reports.company', ['company' => $this->company, 'period' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->where('summary.wonValue', 5000));
});

test('an invalid period is rejected', function () {
    $this->get(route('reports.company', ['company' => $this->company, 'period' => '7d']))
        ->assertSessionHasErrors('period');
});

test('still-active tickets from before the period are included', function () {
    $old = Ticket::factory()->create(['contact_id' => $this->ana->contact_id, 'company_id' => $this->company->company_id]);
    $old->forceFill(['created_at' => now()->subDays(200)])->saveQuietly();

    $closedOld = Ticket::factory()->status(TicketStatus::Closed)->create(['contact_id' => $this->ana->contact_id, 'company_id' => $this->company->company_id]);
    $closedOld->forceFill(['created_at' => now()->subDays(200)])->saveQuietly();

    $this->get(route('reports.company', ['company' => $this->company, 'period' => '30d']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets', 1)
            ->where('tickets.0.ticket_id', $old->ticket_id)
            ->where('support.total', 0));
});

test('the customer report only covers that contact', function () {
    closedDeal($this->ana, DealStage::Won, 1000, 10);
    closedDeal($this->ben, DealStage::Won, 9000, 10);
    Task::factory()->create(['contact_id' => $this->ana->contact_id]);
    Task::factory()->create(['contact_id' => $this->ben->contact_id]);

    $this->get(route('reports.contact', $this->ana))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Account')
            ->where('kind', 'contact')
            ->where('account.full_name', $this->ana->full_name)
            ->where('summary.wonValue', 1000)
            ->has('contacts', 0)
            ->has('followUps', 1));
});

test('the reports index lists companies and customers for account reports', function () {
    closedDeal($this->ana, DealStage::Won, 1200, 30);

    $this->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies', 1)
            ->where('companies.0.hash_id', $this->company->hash_id)
            ->where('companies.0.won_value', fn ($value) => (float) $value === 1200.0)
            ->has('customers', 2)
            ->has('generatedAt'));
});

test('the company print template renders the letterhead and sections', function () {
    closedDeal($this->ana, DealStage::Won, 1000, 10);

    $this->get(route('reports.company.print', ['company' => $this->company, 'period' => '90d']))
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('Company account report')
        ->assertSee('Last 90 days')
        ->assertSee($this->company->name)
        ->assertSee('Generated')
        ->assertSee(auth()->user()->name)
        ->assertSeeInOrder(['Won revenue', 'Deals', 'Support tickets', 'Contacts', 'Open follow-ups'])
        ->assertSee('₱1,000');
});

test('the customer print template shows contact details instead of a contact list', function () {
    $this->get(route('reports.contact.print', $this->ana))
        ->assertOk()
        ->assertSee('Customer account report')
        ->assertSee($this->ana->full_name)
        ->assertSee('Contact details');
});

test('the overview print template renders', function () {
    $this->get(route('reports.print'))
        ->assertOk()
        ->assertSee('Business overview report')
        ->assertSeeInOrder(['Value by stage', 'Won vs lost', 'Customer support', 'Accounts'])
        ->assertSee($this->company->name);
});

test('print templates escape user content', function () {
    $this->company->update(['name' => '<script>alert(1)</script>']);

    $this->get(route('reports.company.print', $this->company))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('autoprint only triggers when requested', function () {
    $this->get(route('reports.print'))->assertDontSee('window.print(), 300', false);
    $this->get(route('reports.print', ['autoprint' => 1]))->assertSee('window.print(), 300', false);
});
