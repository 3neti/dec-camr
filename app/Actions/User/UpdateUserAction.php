<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class UpdateUserAction
{
    public function execute(
        int $userId,
        string $userRealName,
        string $userName,
        string $userEmail,
        string $userType,
        ?string $userPassword,
        ?string $userJobTitle,
        string $userAccess,
        int $updatedByUserId,
    ): bool {
        $user = User::query()->findOrFail($userId);

        $user->user_real_name = $userRealName;
        $user->user_job_title = $userJobTitle;
        $user->name = $userName;
        $user->email = $userEmail;
        $user->user_type = $userType;
        $user->user_access = $userAccess;
        $user->modified_by_user_idx = $updatedByUserId;

        if ($userPassword !== null && $userPassword !== '') {
            $user->password = Hash::make($userPassword);
        }

        return (bool) $user->save();
    }
}
