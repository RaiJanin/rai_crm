<?php

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a deal can be created for a contact', function () {
    $contact = Contact::factory()->create();

    $response = $this->post(route('deals.store'), [
        'title' => 'Website redesign',
        'contact_id' => $contact->contact_id,
        'value' => '12500.50',
        'stage' => 'qualified',
        'expected_close_date' => '2026-12-01',
    ]);

    $deal = Deal::sole();

    $response->assertRedirect(route('deals.show', $deal));
    expect($deal->stage)->toBe(DealStage::Qualified)
        ->and($deal->value)->toBe('12500.50')
        ->and($deal->closed_at)->toBeNull();
});

test('deal validation requires a contact and a valid stage', function () {
    $this->post(route('deals.store'), ['title' => 'X', 'value' => 10, 'stage' => 'bogus'])
        ->assertSessionHasErrors(['contact_id', 'stage']);
});

test('the create page pre-selects a contact from the query string', function () {
    $contact = Contact::factory()->create();

    $this->get(route('deals.create', ['contact' => $contact->hash_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('deals/Create')
            ->where('defaults.contact_id', $contact->contact_id));
});

test('deals index filters by stage', function () {
    Deal::factory()->stage(DealStage::Won)->create();
    Deal::factory()->stage(DealStage::Lead)->count(2)->create();

    $this->get(route('deals.index', ['stage' => 'won']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('deals/Index')
            ->has('deals.data', 1));
});

test('moving a deal to a closed stage records when it closed', function () {
    $deal = Deal::factory()->create();

    $this->patch(route('deals.stage', $deal), ['stage' => 'won'])->assertRedirect();

    $deal->refresh();
    expect($deal->stage)->toBe(DealStage::Won)
        ->and($deal->closed_at)->not->toBeNull();

    $this->patch(route('deals.stage', $deal), ['stage' => 'proposal']);

    expect($deal->fresh()->closed_at)->toBeNull();
});

test('the pipeline groups open and recently closed deals', function () {
    Deal::factory()->stage(DealStage::Proposal)->create();
    $oldWin = Deal::factory()->stage(DealStage::Won)->create();
    $oldWin->forceFill(['closed_at' => now()->subYear()])->saveQuietly();

    $this->get(route('deals.pipeline'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('deals/Pipeline')
            ->has('stages', 6)
            ->has('deals', 1));
});

test('deleting a deal cascades to its tasks and restoring brings them back', function () {
    $deal = Deal::factory()->create();
    $task = Task::factory()->create(['deal_id' => $deal->deal_id]);
    $earlierTask = Task::factory()->create(['deal_id' => $deal->deal_id]);

    $this->travel(-1)->hours();
    $earlierTask->delete();
    $this->travelBack();

    $this->delete(route('deals.destroy', $deal))->assertRedirect(route('deals.index'));

    expect($task->fresh()->trashed())->toBeTrue();

    $deal->fresh()->restore();

    expect($task->fresh()->trashed())->toBeFalse()
        ->and($earlierTask->fresh()->trashed())->toBeTrue();
});

test('deleting a contact cascades to their deals', function () {
    $deal = Deal::factory()->create();

    $this->delete(route('contacts.destroy', $deal->contact));

    expect($deal->fresh()->trashed())->toBeTrue();
});
