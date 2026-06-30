<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Models\UserSiteAccess;
use Illuminate\Support\Facades\DB;

final class DeleteUserAction
{
    public function execute(int $userId): bool
    {
        $user = User::query()->findOrFail($userId);

        return DB::transaction(function () use ($user, $userId): bool {
            UserSiteAccess::query()->where('user_idx', (string) $userId)->delete();

            return (bool) $user->delete();
        });
    }
}
