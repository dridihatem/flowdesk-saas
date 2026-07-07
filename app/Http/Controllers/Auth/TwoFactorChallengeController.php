<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('login.id');
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $userId = $request->session()->get('login.id');
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login');
        }

        $code = (string) $request->input('code');
        $ok = $this->twoFactor->verifyOtp((string) $user->two_factor_secret, $code)
            || $this->twoFactor->consumeRecoveryCode($user, $code);

        if (! $ok) {
            throw ValidationException::withMessages([
                'code' => __('Invalid authentication code.'),
            ]);
        }

        $remember = (bool) $request->session()->get('login.remember', false);
        Auth::loginUsingId($user->id, $remember);
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        return redirect()->intended(flowdesk_post_login_redirect($user));
    }
}
