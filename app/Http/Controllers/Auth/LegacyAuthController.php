<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateLegacyUserAction;
use App\Actions\Auth\RequestTemporaryPasswordAction;
use App\Http\Requests\Auth\LegacyLoginRequest;
use App\Http\Requests\Auth\LegacyPasswordResetRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LegacyAuthController extends Controller
{
    public function __construct(
        private readonly AuthenticateLegacyUserAction $authenticateLegacyUserAction,
        private readonly RequestTemporaryPasswordAction $requestTemporaryPasswordAction,
    ) {}

    public function loginPage(Request $request)
    {
        return Inertia::render('auth/Login', [
            'status' => $request->session()->get('status'),
            'fail' => $request->session()->get('fail'),
            'canResetPassword' => true,
            'legacyApplicationTitle' => 'Centralized Automated Meter Reading',
        ]);
    }

    public function passwordResetPage(Request $request)
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'legacyApplicationTitle' => 'Centralized Automated Meter Reading',
        ]);
    }

    public function loginUser(LegacyLoginRequest $request)
    {
        $result = $this->authenticateLegacyUserAction->execute(
            (string) $request->input('user_name'),
            (string) $request->input('InputPassword'),
            $request
        );

        if (! $result['ok']) {
            return match ($result['reason']) {
                'not_registered' => back()->with('fail', 'This Username is not Registered.'),
                'invalid_password' => back()->with('fail', 'Incorrect Password'),
                default => back()->with('fail', 'Authentication failed'),
            };
        }

        return redirect('/site');
    }

    public function requestTemporaryPassword(LegacyPasswordResetRequest $request)
    {
        $result = $this->requestTemporaryPasswordAction->execute(
            (string) $request->input('user_email_address')
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }

    public function logout(Request $request)
    {
        if ($request->session()->has('loginID')) {
            $request->session()->forget(['loginID', 'site_current_tab']);
        }

        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
