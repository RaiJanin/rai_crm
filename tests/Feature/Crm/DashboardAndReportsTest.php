<?php

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the dashboard shows live pipeline stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Deal::factory()->stage(DealStage::Proposal)->create(['value' => 1000]);
    Deal::factory()->stage(DealStage::Lead)->create(['value' => 500]);
    Deal::factory()->create(['value' => 2000])->update(['stage' => DealStage::Won]);
    Task::factory()->create(['responsible_person_id' => $user->id, 'due_date' => now()->subDay()]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.openDeals', 2)
            ->where('stats.openDealsValue', 1500)
            ->where('stats.wonThisMonth', 1)
            ->where('stats.overdueTasks', 1)
            ->has('myTasks', 1)
            ->has('recentDeals', 3));
});

test('reports summarise deals by stage and outcome', function () {
    $this->actingAs(User::factory()->create());

    Deal::factory()->create(['value' => 300])->update(['stage' => DealStage::Won]);
    Deal::factory()->create()->update(['stage' => DealStage::Lost]);

    $this->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->where('winRate', 50)
            ->has('byStage', 6)
            ->has('monthly', 6)
            ->where('monthly.5.won', 1)
            ->where('monthly.5.lost', 1));
});
