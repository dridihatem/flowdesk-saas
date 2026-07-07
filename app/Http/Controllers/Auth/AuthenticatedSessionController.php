<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\MathCaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const CAPTCHA_CONTEXT = 'auth-login';

    /**
     * Display the login view.
     */
    public function create(MathCaptchaService $mathCaptcha): View
    {
        return view('auth.login', [
            'captcha' => $mathCaptcha->generate(self::CAPTCHA_CONTEXT),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, MathCaptchaService $mathCaptcha): RedirectResponse
    {
        $captchaError = $mathCaptcha->validate($request, self::CAPTCHA_CONTEXT);
        if ($captchaError !== null) {
            return back()
                ->withErrors(['_captcha_answer' => $captchaError])
                ->withInput($request->except('password', '_captcha_token', '_captcha_answer'));
        }

        $request->authenticate();

        $user = $request->user();
        if ($user && $user->hasTwoFactorEnabled()) {
            $remember = $request->boolean('remember');
            Auth::logout();
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $remember);
            $request->session()->regenerate();

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();

        return redirect()->intended(flowdesk_post_login_redirect($request->user()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
