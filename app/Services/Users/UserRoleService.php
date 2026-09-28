<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserRoleService
{
    /**
     * Give the user with this email a CRM role.
     *
     * Role is not mass-assignable (users cannot pick their own role at
     * registration), so it is set explicitly here.
     *
     * @throws ModelNotFoundException<User>
     */
    public function assign(string $email, UserRole $role): User
    {
        $user = User::where('email', $email)->firstOrFail();

        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
