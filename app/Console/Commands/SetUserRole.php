<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Services\Users\UserRoleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

#[Signature('crm:role {email : Email of the user} {role=admin : admin or member}')]
#[Description('Set a CRM user\'s role (admin or member)')]
class SetUserRole extends Command
{
    public function handle(UserRoleService $roles): int
    {
        $role = UserRole::tryFrom((string) $this->argument('role'));

        if ($role === null) {
            $this->error('Role must be one of: '.implode(', ', array_column(UserRole::cases(), 'value')).'.');

            return self::INVALID;
        }

        try {
            $user = $roles->assign((string) $this->argument('email'), $role);
        } catch (ModelNotFoundException) {
            $this->error("No user with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $this->info("{$user->email} is now {$role->value}.");

        return self::SUCCESS;
    }
}
