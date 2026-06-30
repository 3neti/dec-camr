<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

final class DeleteUserAction
{
    public function execute(int $userId): bool
    {
        $user = User::query()->findOrFail($userId);

        return $user->delete();
    }
}
