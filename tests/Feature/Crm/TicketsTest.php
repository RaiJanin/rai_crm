<?php

use App\Enums\SlaState;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a ticket can be logged for a customer and inherits their company', function () {
    $contact = Contact::factory()->for(Company::factory())->create();

    $response = $this->post(route('tickets.store'), [
        'subject' => 'SMS reminders stopped sending',
        'description' => 'Patients have not received reminders since Monday.',
        'contact_id' => $contact->contact_id,
        'type' => 'incident',
        'priority' => 'high',
        'channel' => 'phone',
        'assignee_id' => $this->user->id,
    ]);

    $ticket = Ticket::sole();

    $response->assertRedirect(route('tickets.show', $ticket));
    expect($ticket->status)->toBe(TicketStatus::New)
        ->and($ticket->company_id)->toBe($contact->company_id)
        ->and($ticket->created_by)->toBe($this->user->id)
        ->and($ticket->reference)->toBe(sprintf('TCK-%05d', $ticket->ticket_id));
});

test('ticket validation rejects unknown enum values', function () {
    $this->post(route('tickets.store'), ['subject' => 'x', 'priority' => 'critical'])
        ->assertSessionHasErrors(['description', 'contact_id', 'type', 'priority', 'channel']);
});

test('sla targets follow the priority', function () {
    $this->freezeSecond();

    $ticket = Ticket::factory()->priority(TicketPriority::Urgent)->create();

    expect($ticket->first_response_due_at->equalTo(now()->addHour()))->toBeTrue()
        ->and($ticket->resolution_due_at->equalTo(now()->addHours(4)))->toBeTrue();

    $ticket->update(['priority' => TicketPriority::Low]);

    expect($ticket->resolution_due_at->equalTo(now()->addHours(120)))->toBeTrue();
});

test('a ticket past its target is reported as breached', function () {
    $ticket = Ticket::factory()->priority(TicketPriority::Urgent)->create();

    $this->travel(2)->hours();

    expect($ticket->fresh()->sla()['state'])->toBe(SlaState::Breached)
        ->and(Ticket::breached()->count())->toBe(1);
});

test('logging a reply to the customer meets the first response target', function () {
    $ticket = Ticket::factory()->create();

    $this->post(route('comments.store'), [
        'commentable_type' => 'ticket',
        'commentable_id' => $ticket->ticket_id,
        'body' => 'Internal: checking logs first.',
        'is_public' => 0,
    ]);

    expect($ticket->fresh()->first_responded_at)->toBeNull();

    $this->post(route('comments.store'), [
        'commentable_type' => 'ticket',
        'commentable_id' => $ticket->ticket_id,
        'body' => 'Thanks for reporting this — we are on it.',
        'is_public' => 1,
    ]);

    $ticket->refresh();
    expect($ticket->first_responded_at)->not->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->comments()->where('is_public', true)->count())->toBe(1);
});

test('only tickets accept customer replies', function () {
    $deal = Deal::factory()->create();

    $this->post(route('comments.store'), [
        'commentable_type' => 'deal',
        'commentable_id' => $deal->deal_id,
        'body' => 'Hello',
        'is_public' => 1,
    ])->assertSessionHasErrors('is_public');
});

test('resolving records the resolution and satisfaction, reopening clears it', function () {
    $ticket = Ticket::factory()->create();

    $this->patch(route('tickets.triage', $ticket), [
        'status' => 'resolved',
        'resolution' => 'Re-enabled the SMS gateway.',
        'satisfaction' => 5,
    ])->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->satisfaction)->toBe(5)
        ->and($ticket->sla()['state'])->toBe(SlaState::Met);

    $this->patch(route('tickets.triage', $ticket), ['status' => 'open']);

    expect($ticket->fresh()->resolved_at)->toBeNull();
});

test('closing a ticket stamps closed and resolved times', function () {
    $ticket = Ticket::factory()->create();

    $this->patch(route('tickets.triage', $ticket), ['status' => 'closed']);

    $ticket->refresh();
    expect($ticket->closed_at)->not->toBeNull()
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('satisfaction must be between one and five', function () {
    $ticket = Ticket::factory()->create();

    $this->patch(route('tickets.triage', $ticket), ['satisfaction' => 6])
        ->assertSessionHasErrors('satisfaction');
});

test('the ticket list defaults to active tickets, most urgent first', function () {
    Ticket::factory()->priority(TicketPriority::Low)->create();
    Ticket::factory()->priority(TicketPriority::Urgent)->create();
    Ticket::factory()->status(TicketStatus::Closed)->create();

    $this->get(route('tickets.all-tickets'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/Index')
            ->has('tickets.data', 2)
            ->where('tickets.data.0.priority', 'urgent')
            ->has('tickets.data.0.sla.state')
            ->where('counts.active', 2));
});

test('my tickets only lists tickets assigned to me', function () {
    Ticket::factory()->create(['assignee_id' => $this->user->id]);
    Ticket::factory()->create(['assignee_id' => User::factory()->create()->id]);

    $this->get(route('tickets.my-tickets'))
        ->assertInertia(fn (Assert $page) => $page->where('scope', 'mine')->has('tickets.data', 1));
});

test('the ticket page shows the conversation and customer history', function () {
    $ticket = Ticket::factory()->create();
    Ticket::factory()->create(['contact_id' => $ticket->contact_id]);

    $this->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/Show')
            ->where('ticket.reference', $ticket->reference)
            ->has('ticket.sla')
            ->has('history', 1));
});

test('a task on a ticket defaults to the ticket customer', function () {
    $ticket = Ticket::factory()->create();

    $this->post(route('tasks.store'), [
        'description' => 'Call back after the gateway restart',
        'ticket_id' => $ticket->ticket_id,
        'status' => 'pending',
    ])->assertSessionHasNoErrors();

    expect(Task::sole()->contact_id)->toBe($ticket->contact_id);
});

test('deleting a contact moves their tickets to the trash and restores them together', function () {
    $ticket = Ticket::factory()->create();
    $contact = $ticket->contact;

    $contact->delete();
    expect($ticket->fresh()->trashed())->toBeTrue();

    $contact->restore();
    expect($ticket->fresh()->trashed())->toBeFalse();
});

test('admins can restore a trashed ticket', function () {
    $this->actingAs(User::factory()->admin()->create());
    $ticket = Ticket::factory()->create();
    $ticket->delete();

    $this->get(route('trash.tickets'))
        ->assertInertia(fn (Assert $page) => $page->where('type', 'tickets')->has('items.data', 1));

    $this->patch(route('trash.restore', ['type' => 'tickets', 'hash' => $ticket->hash_id]))->assertRedirect();

    expect($ticket->fresh()->trashed())->toBeFalse();
});

test('dashboard and reports include support metrics', function () {
    $ticket = Ticket::factory()->priority(TicketPriority::High)->create();
    $this->patch(route('tickets.triage', $ticket), ['status' => 'resolved', 'resolution' => 'Fixed', 'satisfaction' => 4]);
    Ticket::factory()->create();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('stats.openTickets', 1));

    $this->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('support.total', 2)
            ->where('support.slaCompliance', 100)
            ->where('support.csat.average', 4));
});

test('contact pages list the customer tickets', function () {
    $ticket = Ticket::factory()->create();

    $this->get(route('contacts.show', $ticket->contact))
        ->assertInertia(fn (Assert $page) => $page->has('tickets', 1));
});
