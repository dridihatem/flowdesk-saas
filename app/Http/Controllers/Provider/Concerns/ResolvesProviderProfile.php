<?php

namespace App\Http\Controllers\Provider\Concerns;

use App\Models\Provider;
use Illuminate\View\View;

trait ResolvesProviderProfile
{
    protected function providerOrAbort(): Provider
    {
        $user = auth()->user();
        abort_if(! $user || ! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        abort_if(! $provider, 403);

        return $provider;
    }

    protected function providerOrNoProfileView(): Provider|View
    {
        $user = auth()->user();
        abort_if(! $user || ! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $provider) {
            return view('provider.no-profile');
        }

        return $provider;
    }
}
