<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('record urls use hashes instead of numeric ids', function () {
    $deal = Deal::factory()->create();

    $url = route('deals.show', $deal);

    expect($url)->not->toEndWith('/deals/'.$deal->deal_id)
        ->and($url)->toEndWith('/deals/'.$deal->hash_id)
        ->and($deal->hash_id)->toHaveLength(10);

    $this->get($url)->assertOk();
});

test('numeric ids and tampered hashes are not found', function () {
    $deal = Deal::factory()->create();

    $this->get('/deals/'.$deal->deal_id)->assertNotFound();
    $this->get('/deals/'.$deal->hash_id.'x')->assertNotFound();
    $this->get('/deals/not-a-hash')->assertNotFound();
});

test('hashes are specific to a record type', function () {
    $contact = Contact::factory()->create();
    $company = Company::factory()->create();

    expect($contact->contact_id)->toBe($company->company_id)
        ->and($contact->hash_id)->not->toBe($company->hash_id);

    $this->get('/companies/'.$contact->hash_id)->assertNotFound();
});

test('hash ids are included in page props', function () {
    $deal = Deal::factory()->create();

    $this->get(route('deals.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('deals.data.0.hash_id', $deal->hash_id)
            ->where('deals.data.0.contact.hash_id', $deal->contact->hash_id));
});

test('trash actions reject numeric ids', function () {
    $deal = Deal::factory()->create();
    $deal->delete();

    $this->patch("/trash/deals/{$deal->deal_id}/restore")->assertNotFound();

    expect($deal->fresh()->trashed())->toBeTrue();
});
