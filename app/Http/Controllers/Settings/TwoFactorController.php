<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    private const SESSION_PENDING = 'two_factor_pending_secret';

    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        $pendingSecret = null;
        $qrSvg = null;
        $encrypted = $request->session()->get(self::SESSION_PENDING);
        if (is_string($encrypted) && $encrypted !== '') {
            try {
                $pendingSecret = decrypt($encrypted);
                $qrSvg = $this->twoFactor->qrCodeSvg(
                    (string) config('app.name'),
                    (string) $user->email,
                    $pendingSecret,
                );
            } catch (\Throwable) {
                $request->session()->forget(self::SESSION_PENDING);
            }
        }

        return view('settings.two-factor', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'qrSvg' => $qrSvg,
            'pendingEnrollment' => $pendingSecret !== null,
        ]);
    }

    public function prepare(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasTwoFactorEnabled(), 403);

        $secret = $this->twoFactor->generateSecretKey();
        $request->session()->put(self::SESSION_PENDING, encrypt($secret));

        return redirect()->route('settings.two-factor')
            ->with('status', __('Scan the QR code with your authenticator app, then enter the code to confirm.'));
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->hasTwoFactorEnabled(), 403);

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $encrypted = $request->session()->get(self::SESSION_PENDING);
        if (! is_string($encrypted) || $encrypted === '') {
            return redirect()->route('settings.two-factor')
                ->withErrors(['code' => __('Start setup again before confirming.')]);
        }

        try {
            $secret = decrypt($encrypted);
        } catch (\Throwable) {
            $request->session()->forget(self::SESSION_PENDING);

            return redirect()->route('settings.two-factor')
                ->withErrors(['code' => __('Your setup session expired. Please start again.')]);
        }

        if (! $this->twoFactor->verifyOtp($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => __('Invalid code.'),
            ]);
        }

        [$plainRecovery, $hashedRecovery] = $this->twoFactor->createRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $hashedRecovery,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget(self::SESSION_PENDING);

        return redirect()->route('settings.two-factor')
            ->with('status', __('Two-factor authentication is enabled.'))
            ->with('two_factor_recovery_codes_plain', $plainRecovery);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->hasTwoFactorEnabled(), 403);

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget(self::SESSION_PENDING);

        return redirect()->route('settings.two-factor')
            ->with('status', __('Two-factor authentication has been disabled.'));
    }
}
