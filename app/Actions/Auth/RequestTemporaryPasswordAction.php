<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Handle legacy temporary password requests and return legacy response contract
 * values used by downstream authentication behavior.
 */
final class RequestTemporaryPasswordAction
{
    public function execute(string $email): array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return [
                'message' => 'Email Not Found!',
                'found' => false,
            ];
        }

        if (! $email || $email !== $user->email) {
            return [
                'message' => 'Incorrect Email!',
                'found' => false,
            ];
        }

        $temporaryPassword = $this->generateRandomPassword();
        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
        ])->save();

        return [
            'message' => 'Email sent successfully!',
            'found' => true,
        ];
    }

    private function generateRandomPassword(int $length = 6): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        $maxIndex = strlen($characters) - 1;

        for ($index = 0; $index < $length; $index++) {
            $password .= $characters[random_int(0, $maxIndex)];
        }

        return $password;
    }
}
