<?php

namespace App\Services;

use App\Models\User;

class ProfileHubService
{
    /**
     * @return list<string>
     */
    public function groupOrder(): array
    {
        return ['account', 'company', 'identity', 'marketing', 'security'];
    }

    public function groupLabel(string $key): string
    {
        return match ($key) {
            'account' => __('Profile group account'),
            'company' => __('Profile group company'),
            'identity' => __('Profile group identity'),
            'marketing' => __('Profile group marketing'),
            'security' => __('Profile group security'),
            default => $key,
        };
    }

    /**
     * Link cards grouped by theme (account section uses inline forms instead).
     *
     * @return list<array{key: string, label: string, cards: list<array{icon: string, title: string, summary: string, route: string}>}>
     */
    public function linkGroups(User $user, PlanLimitService $planLimits): array
    {
        $cards = [];
        $company = $user->company;
        $hasWorkspace = $company !== null && $user->hasAnyRole(['company_admin', 'team_member']);

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

        if ($hasWorkspace) {
            $push([
                'group' => 'company',
                'icon' => 'contact',
                'title' => __('Workspace contact'),
                'summary' => __('Settings hub workspace contact'),
                'route' => 'settings.workspace-contact',
            ]);
            $push([
                'group' => 'company',
                'icon' => 'branding',
                'title' => __('Branding'),
                'summary' => __('Settings hub branding'),
                'route' => 'settings.branding',
            ]);
            $push([
                'group' => 'company',
                'icon' => 'currency',
                'title' => __('Default currency'),
                'summary' => __('Settings hub currency'),
                'route' => 'settings.workspace-currency',
            ]);
            $push([
                'group' => 'identity',
                'icon' => 'appearance',
                'title' => __('Appearance & theme'),
                'summary' => __('Profile hub appearance summary'),
                'route' => 'settings.appearance',
            ]);
            $push([
                'group' => 'identity',
                'icon' => 'branding',
                'title' => __('Branding'),
                'summary' => __('Profile hub branding identity summary'),
                'route' => 'settings.branding',
            ]);
            $push([
                'group' => 'marketing',
                'icon' => 'marketing',
                'title' => __('Marketing & SEO'),
                'summary' => __('Settings hub marketing'),
                'route' => 'marketing.hub',
                'plan_feature' => 'marketing_hub',
            ]);
            $push([
                'group' => 'marketing',
                'icon' => 'embed',
                'title' => __('Widget embed'),
                'summary' => __('Settings hub widget'),
                'route' => 'settings.widget-embed',
                'plan_feature' => 'widgets',
            ]);
            $push([
                'group' => 'marketing',
                'icon' => 'integrations',
                'title' => __('Marketing integrations'),
                'summary' => __('Settings hub marketing integrations'),
                'route' => 'settings.marketing-integrations',
                'roles' => ['company_admin', 'team_member'],
                'plan_feature' => 'email_marketing',
            ]);
            $push([
                'group' => 'marketing',
                'icon' => 'email-marketing',
                'title' => __('Email marketing'),
                'summary' => __('Settings hub email marketing'),
                'route' => 'email-marketing.index',
                'plan_feature' => 'email_marketing',
            ]);
        }

        $push([
            'group' => 'security',
            'icon' => 'two-factor',
            'title' => __('Two-factor authentication'),
            'summary' => __('Settings hub two factor'),
            'route' => 'settings.two-factor',
        ]);
        $push([
            'group' => 'security',
            'icon' => 'security',
            'title' => __('Security'),
            'summary' => __('Settings hub security'),
            'route' => 'settings.security',
        ]);

        if ($user->hasRole('company_admin')) {
            $push([
                'group' => 'security',
                'icon' => 'team',
                'title' => __('Team & roles'),
                'summary' => __('Settings hub team'),
                'route' => 'settings.team',
            ]);
        }

        $collected = collect($cards);

        return collect($this->groupOrder())
            ->map(function (string $key) use ($collected): ?array {
                if ($key === 'account') {
                    return [
                        'key' => $key,
                        'label' => $this->groupLabel($key),
                        'cards' => [],
                    ];
                }

                $items = $collected->where('group', $key)->values()->all();
                if ($items === []) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => $this->groupLabel($key),
                    'cards' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
