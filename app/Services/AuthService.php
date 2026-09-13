<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(string $login, string $password): array
    {
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $login,
            'password' => $password,
        ];

        // Laravel's Auth::attempt verifies credentials against the configured User provider
        if (! Auth::attempt($credentials)) {
            abort(401, 'Invalid credentials.');
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            abort(403, 'Account is inactive.');
        }

        // Fetch user's assigned branch
        $branch = $user->assignedBranch();

        // Non-owners must have an active assigned branch
        if (! $user->isOwner() && ! $branch) {
            abort(403, 'User is not assigned to any active branch.');
        }

        if ($branch && ! $branch->isActive()) {
            abort(403, 'Assigned branch is inactive.');
        }

        $branchUuid = $branch?->uuid;
        $permissions = PermissionService::permissionsFor($user, $branchUuid);
        $roleName    = PermissionService::roleNameFor($user, $branchUuid);
        $token       = $user->createToken('auth-token')->plainTextToken;

        return [
            'token'       => $token,
            'user'        => $user,
            'branch'      => $branch,
            'role'        => $roleName,
            'status'      => $user->status?->value ?? (string) $user->status,
            'permissions' => $permissions,
        ];
    }

    public function me(User $user): array
    {
        $branch = $user->assignedBranch();
        $branchUuid = $branch?->uuid;

        $permissions = PermissionService::permissionsFor($user, $branchUuid);
        $roleName    = PermissionService::roleNameFor($user, $branchUuid);

        return [
            'token'       => null,
            'user'        => $user,
            'branch'      => $branch,
            'role'        => $roleName,
            'status'      => $user->status?->value ?? (string) $user->status,
            'permissions' => $permissions,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
