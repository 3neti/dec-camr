<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Authenticate legacy login requests using the legacy request shape and maintain the
 * `loginID` session contract used across subsequent migration slices.
 */
final class AuthenticateLegacyUserAction
{
    public function __construct(private readonly AuthManager $auth) {}

    /**
     * @return array{ok: bool, reason: 'not_registered'|'invalid_password'|null, user_id: int|null}
     */
    public function execute(string $userName, string $password, Request $request): array
    {
        $user = User::query()->where('name', $userName)->first();

        if ($user === null) {
            return [
                'ok' => false,
                'reason' => 'not_registered',
                'user_id' => null,
            ];
        }

        if (! Hash::check($password, $user->password)) {
            return [
                'ok' => false,
                'reason' => 'invalid_password',
                'user_id' => null,
            ];
        }

        $this->auth->guard()->login($user);
        $request->session()->put('loginID', $user->id);

        return [
            'ok' => true,
            'reason' => null,
            'user_id' => $user->id,
        ];
    }
}
