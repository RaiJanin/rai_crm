<?php

use App\Enums\TaskStatus;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a task on a deal inherits the deal contact', function () {
    $deal = Deal::factory()->create();

    $this->post(route('tasks.store'), [
        'description' => 'Send proposal',
        'deal_id' => $deal->deal_id,
        'status' => 'pending',
        'responsible_person_id' => $this->user->id,
    ])->assertRedirect();

    expect(Task::sole()->contact_id)->toBe($deal->contact_id);
});

test('my tasks only shows tasks assigned to me', function () {
    Task::factory()->create(['responsible_person_id' => $this->user->id]);
    Task::factory()->create(['responsible_person_id' => User::factory()->create()->id]);

    $this->get(route('tasks.my-tasks'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Index')
            ->where('scope', 'mine')
            ->has('tasks.data', 1));

    $this->get(route('tasks.all-tasks'))
        ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 2));
});

test('a task can be completed and deleted', function () {
    $task = Task::factory()->create();

    $this->put(route('tasks.update', $task), [
        'description' => $task->description,
        'status' => 'done',
    ])->assertRedirect();

    expect($task->fresh()->status)->toBe(TaskStatus::Done);

    $this->delete(route('tasks.destroy', $task))->assertRedirect();

    expect($task->fresh()->trashed())->toBeTrue();
});
