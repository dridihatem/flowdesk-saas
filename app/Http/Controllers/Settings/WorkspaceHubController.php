<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\PlanLimitService;
use Illuminate\View\View;

class WorkspaceHubController extends Controller
{
    /**
     * @return list<string>
     */
    private function groupOrder(): array
    {
        return ['appearance', 'revenue', 'people', 'system'];
    }

    private function groupLabel(string $key): string
    {
        return match ($key) {
            'appearance' => __('Settings group appearance'),
            'revenue' => __('Settings group revenue'),
            'people' => __('Settings group people'),
            'system' => __('Settings group system'),
            default => $key,
        };
    }

    public function __invoke(PlanLimitService $planLimits): View
    {
        $user = request()->user();
        $cards = [];

        $push = function (array $card) use (&$cards, $user, $planLimits): void {
            $roles = $card['roles'] ?? null;
            if (is_array($roles) && ! $user->hasAnyRole($roles)) {
                return;
            }
            $planFeature = $card['plan_feature'] ?? null;
            if (is_string($planFeature) && $user->company && ! $planLimits->isFeatureEnabled($user->company, $planFeature)) {
                return;
            }
            unset($card['roles'], $card['plan_feature']);
            $cards[] = $card;
        };

        $push([
            'group' => 'appearance',
            'icon' => 'appearance',
            'title' => __('Appearance & theme'),
            'summary' => __('Settings hub appearance'),
            'route' => 'settings.appearance',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'widgets',
            'title' => __('Dashboard & widgets'),
            'summary' => __('Settings hub dashboard'),
            'route' => 'settings.dashboard',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'navigation',
            'title' => __('settings_navigation_title'),
            'summary' => __('settings_hub_navigation'),
            'route' => 'settings.navigation',
            'roles' => ['company_admin'],
        ]);
        $push([
            'group' => 'people',
            'icon' => 'profile',
            'title' => __('Account'),
            'summary' => __('Settings hub account'),
            'route' => 'profile.edit',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'people',
            'icon' => 'team',
            'title' => __('Team & roles'),
            'summary' => __('Settings hub team'),
            'route' => 'settings.team',
            'roles' => ['company_admin'],
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'branding',
            'title' => __('Branding'),
            'summary' => __('Settings hub branding'),
            'route' => 'settings.branding',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'currency',
            'title' => __('Default currency'),
            'summary' => __('Settings hub currency'),
            'route' => 'settings.workspace-currency',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'profile',
            'title' => __('settings_workspace_locale_title'),
            'summary' => __('Settings hub workspace locale'),
            'route' => 'settings.workspace-locale',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'contact',
            'title' => __('Workspace contact'),
            'summary' => __('Settings hub workspace contact'),
            'route' => 'settings.workspace-contact',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'revenue',
            'icon' => 'commission',
            'title' => __('Provider commission tiers'),
            'summary' => __('Settings hub commissions'),
            'route' => 'settings.provider-commissions',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'providers',
        ]);
        $push([
            'group' => 'people',
            'icon' => 'providers',
            'title' => __('Provider recruitment'),
            'summary' => __('Settings hub provider recruitment'),
            'route' => 'settings.provider-recruitment',
            'roles' => ['company_admin'],
            'plan_feature' => 'providers',
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'marketing',
            'title' => __('Marketing & SEO'),
            'summary' => __('Settings hub marketing'),
            'route' => 'marketing.hub',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'marketing_hub',
        ]);
        $push([
            'group' => 'appearance',
            'icon' => 'email-marketing',
            'title' => __('Email marketing'),
            'summary' => __('Settings hub email marketing'),
            'route' => 'email-marketing.index',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'email_marketing',
        ]);
        $push([
            'group' => 'revenue',
            'icon' => 'embed',
            'title' => __('Widget embed'),
            'summary' => __('Settings hub widget'),
            'route' => 'settings.widget-embed',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'widgets',
        ]);
        $push([
            'group' => 'system',
            'icon' => 'modules',
            'title' => __('settings_modules_title'),
            'summary' => __('settings_hub_modules'),
            'route' => 'settings.modules',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'modules',
        ]);
        $push([
            'group' => 'system',
            'icon' => 'integrations',
            'title' => __('Marketing integrations'),
            'summary' => __('Settings hub marketing integrations'),
            'route' => 'settings.marketing-integrations',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'email_marketing',
        ]);
        $push([
            'group' => 'system',
            'icon' => 'integrations',
            'title' => __('workspace_api_connect_title'),
            'summary' => __('settings_hub_api_connect'),
            'route' => 'settings.api-connect',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'system',
            'icon' => 'integrations',
            'title' => __('workspace_ai_agent_title'),
            'summary' => __('settings_hub_ai_agent'),
            'route' => 'settings.ai-agent',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'workspace_ai_agent',
        ]);
        $push([
            'group' => 'system',
            'icon' => 'smtp',
            'title' => __('SMTP'),
            'summary' => __('Settings hub smtp'),
            'route' => 'settings.smtp',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'system',
            'icon' => 'calendar',
            'title' => __('Calendar & scheduling'),
            'summary' => __('Settings hub calendar scheduling'),
            'route' => 'settings.calendar-scheduling',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'calendar',
        ]);
        $push([
            'group' => 'system',
            'icon' => 'calendar',
            'title' => __('settings_connectivity_title'),
            'summary' => __('Settings hub google calendar'),
            'route' => 'settings.google-calendar',
            'roles' => ['company_admin', 'team_member'],
            'plan_feature' => 'projects',
        ]);
        $push([
            'group' => 'revenue',
            'icon' => 'documents',
            'title' => __('Invoice documents'),
            'summary' => __('Settings hub invoice documents'),
            'route' => 'settings.invoice-documents',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'revenue',
            'icon' => 'billing',
            'title' => __('Client payment methods'),
            'summary' => __('Settings hub payment gateways'),
            'route' => 'settings.payment-gateways',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'revenue',
            'icon' => 'invoices',
            'title' => __('VAT & stamp'),
            'summary' => __('Settings hub billing tax'),
            'route' => 'settings.billing-tax',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'people',
            'icon' => 'security',
            'title' => __('Security'),
            'summary' => __('Settings hub security'),
            'route' => 'settings.security',
            'roles' => ['company_admin', 'team_member'],
        ]);
        $push([
            'group' => 'people',
            'icon' => 'two-factor',
            'title' => __('Two-factor authentication'),
            'summary' => __('Settings hub two factor'),
            'route' => 'settings.two-factor',
            'roles' => ['company_admin', 'team_member'],
        ]);

        $collected = collect($cards);
        $groups = collect($this->groupOrder())
            ->map(function (string $key) use ($collected): ?array {
                $items = $collected->where('group', $key)->values()->all();
                if ($items === []) {
                    return null;
                }

                return [
                    'label' => $this->groupLabel($key),
                    'cards' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return view('settings.workspace', ['groups' => $groups]);
    }
}
