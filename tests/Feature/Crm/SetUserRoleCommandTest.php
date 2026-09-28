<?php

use App\Enums\UserRole;
use App\Models\User;

test('crm:role promotes a user to admin by default', function () {
    $user = User::factory()->create(['email' => 'ana@example.com']);

    $this->artisan('crm:role', ['email' => 'ana@example.com'])
        ->expectsOutput('ana@example.com is now admin.')
        ->assertSuccessful();

    expect($user->fresh()->role)->toBe(UserRole::Admin);
});

test('crm:role can demote an admin to member', function () {
    $user = User::factory()->admin()->create();

    $this->artisan('crm:role', ['email' => $user->email, 'role' => 'member'])->assertSuccessful();

    expect($user->fresh()->role)->toBe(UserRole::Member);
});

test('crm:role rejects unknown emails and roles', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->artisan('crm:role', ['email' => 'nobody@example.com'])
        ->expectsOutput('No user with email nobody@example.com.')
        ->assertFailed();

    $this->artisan('crm:role', ['email' => 'ana@example.com', 'role' => 'owner'])
        ->expectsOutput('Role must be one of: admin, member.')
        ->assertExitCode(2);
});
