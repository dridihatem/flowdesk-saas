<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends Controller
{
    private const PROVIDER_MAP = [
        'github' => 'github',
        'google' => 'google',
        'linkedin' => 'linkedin-openid',
    ];

    public function redirect(string $provider)
    {
        $driver = $this->driverName($provider);

        return Socialite::driver($driver)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $driver = $this->driverName($provider);

        /** @var SocialiteUser $socialUser */
        $socialUser = Socialite::driver($driver)->user();

        $email = $socialUser->getEmail();

        if ($email === null || $email === '') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('This provider did not return an email. Use another login method.')]);
        }

        $existingAccount = SocialAccount::query()
            ->where('provider', $driver)
            ->where('provider_user_id', (string) $socialUser->getId())
            ->first();

        if ($existingAccount) {
            return $this->completeLogin($existingAccount->user);
        }

        $userByEmail = User::query()->where('email', $email)->first();

        if ($userByEmail) {
            $this->attachAccount($userByEmail, $driver, $socialUser);

            return $this->completeLogin($userByEmail);
        }

        session([
            'oauth_pending' => [
                'driver' => $driver,
                'provider_label' => $provider,
                'provider_user_id' => (string) $socialUser->getId(),
                'email' => $email,
                'name' => $socialUser->getName()
                    ?? $socialUser->getNickname()
                    ?? Str::before($email, '@'),
                'avatar_url' => $socialUser->getAvatar(),
            ],
        ]);

        return redirect()->route('oauth.company.create');
    }

    private function completeLogin(User $user): RedirectResponse
    {
        Auth::login($user);

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            request()->session()->put('login.id', $user->id);
            request()->session()->put('login.remember', false);
            request()->session()->regenerate();

            return redirect()->route('two-factor.login');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function attachAccount(User $user, string $driver, SocialiteUser $socialUser): void
    {
        SocialAccount::query()->updateOrCreate(
            [
                'provider' => $driver,
                'provider_user_id' => (string) $socialUser->getId(),
            ],
            [
                'user_id' => $user->id,
                'email' => $socialUser->getEmail(),
                'avatar_url' => $socialUser->getAvatar(),
            ],
        );
    }

    private function driverName(string $provider): string
    {
        if (! isset(self::PROVIDER_MAP[$provider])) {
            abort(404);
        }

        return self::PROVIDER_MAP[$provider];
    }
}
