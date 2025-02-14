<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService
{
    public function getByInternalUserId(string $internalUserId): User
    {
        $user = User::query()->where('internal_user_id', $internalUserId)->first();

        if (! $user) {
            throw new ModelNotFoundException("User with ID $internalUserId not found.");
        }

        return $user;
    }
}
