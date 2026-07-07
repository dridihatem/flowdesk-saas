<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ResolvesProviderProfile;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderRemittanceRequest;
use App\Services\ProviderCommissionBalanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesProviderProfile;

    public function __invoke(Request $request, ProviderCommissionBalanceService $balances): View
    {
        $user = $request->user();
        abort_if(! $user->hasRole('business_provider'), 403);

        $provider = Provider::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->with('company')
            ->first();

        if (! $provider) {
            return view('provider.no-profile');
        }

        $openProjects = Project::query()->withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('provider_id', $provider->id)
            ->latest()
            ->take(8)
            ->get();

        $summary = $balances->summary($provider);
        $recentCommissions = $balances->recentCommissions($provider, 5);

        $recentRemittanceRequests = ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('provider_id', $provider->id)
            ->latest()
            ->take(5)
            ->get();

        return view('provider.dashboard', compact(
            'provider',
            'openProjects',
            'summary',
            'recentCommissions',
            'recentRemittanceRequests',
        ));
    }
}
