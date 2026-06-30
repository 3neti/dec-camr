<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    public function execute(
        string $userRealName,
        string $userName,
        string $userEmail,
        string $userPassword,
        string $userType,
        string $userAccess,
        ?string $userJobTitle,
        int $createdByUserId,
    ): User {
        $user = new User;
        $user->user_real_name = $userRealName;
        $user->user_job_title = $userJobTitle;
        $user->name = $userName;
        $user->email = $userEmail;
        $user->password = Hash::make($userPassword);
        $user->user_type = $userType;
        $user->user_access = $userAccess;
        $user->created_by_user_idx = $createdByUserId;

        $user->save();

        return $user;
    }
}
