<?php

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a note can be added to a deal', function () {
    $deal = Deal::factory()->create();

    $this->post(route('comments.store'), [
        'commentable_type' => 'deal',
        'commentable_id' => $deal->deal_id,
        'body' => 'Called, left voicemail.',
    ])->assertRedirect();

    expect($deal->comments()->sole()->body)->toBe('Called, left voicemail.');
});

test('notes reject unknown record types', function () {
    $this->post(route('comments.store'), [
        'commentable_type' => 'user',
        'commentable_id' => $this->user->id,
        'body' => 'Nope',
    ])->assertSessionHasErrors('commentable_type');
});

test('only the author or an admin can delete a note', function () {
    $deal = Deal::factory()->create();
    $comment = $deal->comments()->create(['user_id' => User::factory()->create()->id, 'body' => 'Theirs']);

    $this->delete(route('comments.destroy', $comment))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('comments.destroy', $comment))
        ->assertRedirect();

    expect(Comment::count())->toBe(0);
});

test('files can be uploaded, downloaded and removed', function () {
    Storage::fake('public');
    $deal = Deal::factory()->create();

    $this->post(route('attachments.store'), [
        'attachable_type' => 'deal',
        'attachable_id' => $deal->deal_id,
        'file' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
    ])->assertRedirect();

    $attachment = Attachment::sole();
    expect($attachment->file_name)->toBe('contract.pdf');

    $this->get(route('attachments.download', $attachment))->assertDownload('contract.pdf');

    $this->delete(route('attachments.destroy', $attachment))->assertRedirect();

    Storage::disk('public')->assertMissing($attachment->file_path);
});
