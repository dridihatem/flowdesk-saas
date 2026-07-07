<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Services\AiCreditUsageService;
use App\Services\ModuleRegistry;
use App\Services\NovaVoiceNavigationService;
use App\Services\PlanLimitService;
use App\Services\SubscriptionTrialService;
use App\Services\WorkspaceCalendarService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareWorkspacePlanContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $defaultGates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);
        $defaultAi = [
            'show' => false,
            'unlimited' => false,
            'used' => 0,
            'limit' => null,
            'remaining' => null,
        ];
        View::share('flowdeskInstalledModules', []);
        View::share('flowdeskNovaVoiceNav', ['enabled' => false]);

        if ($user?->company && $user->hasAnyRole(['company_admin', 'team_member'])) {
            $limits = app(PlanLimitService::class);
            $gates = $limits->featureGates($user->company);
            $request->attributes->set('flowdeskPlanGates', $gates);
            View::share('flowdeskPlanGates', $gates);
            View::share('flowdeskTrialBanner', app(SubscriptionTrialService::class)->bannerFor($user->company));
            View::share('flowdeskNavAiCredits', $limits->aiCreditsForNav($user->company));
            View::share('flowdeskAiTaskCredits', app(AiCreditUsageService::class)->publicTaskCosts());
            View::share('flowdeskCalendarNav', $this->calendarNavPreview($user->company, $gates));
            View::share('flowdeskInstalledModules', app(ModuleRegistry::class)->navItemsFor($user->company));
            View::share('flowdeskNovaVoiceNav', app(NovaVoiceNavigationService::class)->clientConfig($user, $gates));
        } else {
            View::share('flowdeskPlanGates', $defaultGates);
            View::share('flowdeskTrialBanner', ['show' => false, 'days_left' => 0, 'ends_at' => null, 'expired' => false, 'plan_name' => null]);
            View::share('flowdeskNavAiCredits', $defaultAi);
            View::share('flowdeskAiTaskCredits', []);
            View::share('flowdeskCalendarNav', $this->calendarNavForClient($user));
            View::share('flowdeskInstalledModules', []);
        }

        return $next($request);
    }

    /**
     * @param  array<string, bool>  $gates
     * @return array<string, mixed>|null
     */
    private function calendarNavPreview(Company $company, array $gates): ?array
    {
        if (! ($gates['calendar'] ?? false)) {
            return null;
        }

        return app(WorkspaceCalendarService::class)->navPreview($company);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function calendarNavForClient(?User $user): ?array
    {
        if (! $user?->company || ! $user->hasRole('client')) {
            return null;
        }

        $limits = app(PlanLimitService::class);
        if (! $limits->isFeatureEnabled($user->company, 'calendar')) {
            return null;
        }

        $client = $user->clientProfile;
        if (! $client) {
            return null;
        }

        return app(WorkspaceCalendarService::class)->navPreview($user->company, (string) $client->id, true);
    }
}
