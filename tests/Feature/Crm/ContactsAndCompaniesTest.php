<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot view contacts', function () {
    auth()->logout();

    $this->get(route('contacts.index'))->assertRedirect(route('login'));
});

test('contacts index lists and searches contacts', function () {
    Contact::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    Contact::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);

    $this->get(route('contacts.index', ['search' => 'ada']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Index')
            ->has('contacts.data', 1)
            ->where('contacts.data.0.full_name', 'Ada Lovelace'));
});

test('a contact can be created with an optional company', function () {
    $company = Company::factory()->create();

    $response = $this->post(route('contacts.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'company_id' => $company->company_id,
    ]);

    $contact = Contact::sole();

    $response->assertRedirect(route('contacts.show', $contact));
    expect($contact->company_id)->toBe($company->company_id)
        ->and($contact->created_by)->toBe(auth()->id());

    $this->post(route('contacts.store'), ['first_name' => 'Solo'])->assertSessionHasNoErrors();
    expect(Contact::where('first_name', 'Solo')->value('company_id'))->toBeNull();
});

test('contact validation rejects bad input', function () {
    $this->post(route('contacts.store'), ['email' => 'not-an-email', 'company_id' => 999])
        ->assertSessionHasErrors(['first_name', 'email', 'company_id']);
});

test('a contact can be viewed and updated', function () {
    $contact = Contact::factory()->create();

    $this->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Show')
            ->where('contact.contact_id', $contact->contact_id));

    $this->patch(route('contacts.update', $contact), ['first_name' => 'Renamed'])
        ->assertRedirect();

    expect($contact->fresh()->first_name)->toBe('Renamed');
});

test('companies can be created, shown and updated', function () {
    $this->post(route('companies.store'), ['name' => 'Acme', 'website' => 'https://acme.test'])
        ->assertRedirect();

    $company = Company::sole();

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('companies/Show'));

    $this->put(route('companies.update', $company), ['name' => 'Acme Inc'])->assertRedirect();

    expect($company->fresh()->name)->toBe('Acme Inc');
});

test('deleting a company keeps its contacts', function () {
    $company = Company::factory()->create();
    $contact = Contact::factory()->for($company)->create();

    $this->delete(route('companies.destroy', $company))->assertRedirect(route('companies.index'));

    expect($company->fresh()->trashed())->toBeTrue()
        ->and($contact->fresh()->trashed())->toBeFalse();
});
