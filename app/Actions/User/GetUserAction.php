<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

final class GetUserAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(int $userId): array
    {
        $user = User::query()
            ->select('id', 'user_real_name', 'user_job_title', 'name', 'user_type', 'user_access', 'email')
            ->findOrFail($userId);

        return [
            'user_id' => $user->id,
            'user_real_name' => $user->user_real_name,
            'user_job_title' => $user->user_job_title,
            'user_name' => $user->name,
            'user_email_address' => $user->email,
            'user_type' => $user->user_type,
            'user_access' => $user->user_access ?? 'Selected',
        ];
    }
}
