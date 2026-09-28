<?php

use App\Models\Attachment;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('members cannot access the trash', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('trash.deals'))->assertForbidden();
});

test('admins see trashed deals', function () {
    $this->actingAs(User::factory()->admin()->create());

    Deal::factory()->create()->delete();
    Deal::factory()->create();

    $this->get(route('trash.deals'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('trash/Index')
            ->where('type', 'deals')
            ->has('items.data', 1));
});

test('admins can restore a contact with its deals', function () {
    $this->actingAs(User::factory()->admin()->create());

    $deal = Deal::factory()->create();
    $deal->contact->delete();

    $this->patch(route('trash.restore', ['type' => 'contacts', 'hash' => $deal->contact->hash_id]))
        ->assertRedirect();

    expect($deal->fresh()->trashed())->toBeFalse();
});

test('a deal cannot be restored while its contact is trashed', function () {
    $this->actingAs(User::factory()->admin()->create());

    $deal = Deal::factory()->create();
    $deal->contact->delete();

    $this->patch(route('trash.restore', ['type' => 'deals', 'hash' => $deal->hash_id]));

    expect($deal->fresh()->trashed())->toBeTrue();
});

test('force deleting a contact removes deals and attachment files', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $deal = Deal::factory()->create();

    $this->post(route('attachments.store'), [
        'attachable_type' => 'deal',
        'attachable_id' => $deal->deal_id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 20),
    ])->assertSessionHasNoErrors();

    $path = Attachment::sole()->file_path;
    Storage::disk('public')->assertExists($path);

    $deal->contact->delete();

    $this->delete(route('trash.destroy', ['type' => 'contacts', 'hash' => $deal->contact->hash_id]))
        ->assertRedirect();

    expect(Contact::withTrashed()->count())->toBe(0)
        ->and(Deal::withTrashed()->count())->toBe(0)
        ->and(Attachment::withTrashed()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});
