<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('crm:role {email} {role=admin}', function (string $email, string $role) {
    $user = User::where('email', $email)->first();
    $newRole = UserRole::tryFrom($role);

    if ($user === null || $newRole === null) {
        $this->error($user === null ? "No user with email {$email}." : 'Role must be admin or member.');

        return 1;
    }

    $user->forceFill(['role' => $newRole])->save();

    $this->info("{$user->email} is now {$newRole->value}.");

    return 0;
})->purpose('Set a CRM user\'s role (admin or member)');
