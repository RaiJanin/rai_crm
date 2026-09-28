<?php

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\CrmDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('the demo seeder builds a realistic pipeline', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(4)
        ->and(Company::count())->toBe(8)
        ->and(Contact::count())->toBe(17)
        ->and(Deal::count())->toBe(16)
        ->and(Task::count())->toBe(34)
        ->and(Ticket::count())->toBe(13)
        ->and(Ticket::active()->count())->toBe(7)
        ->and(Ticket::breached()->count())->toBe(3)
        ->and(Ticket::whereNotNull('satisfaction')->count())->toBe(6);

    // Closed deals keep their historical close dates rather than "now".
    $wonAtlas = Deal::where('title', 'Production scheduling license – 2 plants')->sole();
    expect($wonAtlas->stage)->toBe(DealStage::Won)
        ->and($wonAtlas->closed_at->toDateString())->toBe(now()->subDays(47)->toDateString());

    $this->actingAs(User::where('email', 'test@example.com')->sole())
        ->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page->where('winRate', fn ($rate) => $rate > 50));
});

test('the demo seeder can run on its own', function () {
    $this->seed(CrmDemoSeeder::class);

    expect(User::where('email', 'test@example.com')->sole()->isAdmin())->toBeTrue()
        ->and(Deal::where('stage', DealStage::Won)->where('closed_at', '<', now()->subDays(30))->count())->toBe(3);
});
