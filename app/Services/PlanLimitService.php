<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Form as LeadForm;
use App\Models\FormSubmission;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Project;
use App\Models\Provider;
use App\Models\User;

class PlanLimitService
{
    /**
     * Feature keys stored in plan_limits and/or enforced in the app.
     * A row with limit_value === 0 turns the feature off for that plan.
     * No row, or null limit_value, means no cap (on, unlimited quota).
     */
    public const FEATURE_KEYS = [
        'projects',
        'users',
        'forms',
        'submissions',
        'widgets',
        'ai_credits',
        'analytics',
        'marketing_hub',
        'email_marketing',
        'reports',
        'providers',
        'calendar',
        'modules',
        'premium_tts',
        'workspace_ai_agent',
        'hr',
    ];

    /** @var array<string, Plan|null> */
    private array $resolvedPlans = [];

    /**
     * Whether the plan includes this feature at all (limit 0 = excluded).
     */
    public function isFeatureEnabled(Company $company, string $feature): bool
    {
        return $this->featureEnabledFromPlan($this->resolveActivePlan($company), $feature);
    }

    /**
     * All feature gates for a company in one pass (one subscription/plan query).
     *
     * @return array<string, bool>
     */
    public function featureGates(Company $company): array
    {
        $plan = $this->resolveActivePlan($company);
        $gates = [];
        foreach (self::FEATURE_KEYS as $key) {
            $gates[$key] = $this->featureEnabledFromPlan($plan, $key);
        }

        return $gates;
    }

    public function allows(Company $company, string $feature, int $additionalUsage = 1): bool
    {
        if (! $this->isFeatureEnabled($company, $feature)) {
            return false;
        }

        $limitValue = $this->planLimitValueFromPlan($this->resolveActivePlan($company), $feature);
        if ($limitValue === null) {
            return true;
        }

        $needed = max(1, $additionalUsage);

        return ($this->currentUsage($company, $feature) + $needed) <= $limitValue;
    }

    public function assertAllows(Company $company, string $feature, int $additionalUsage = 1): void
    {
        if ($this->allows($company, $feature, $additionalUsage)) {
            return;
        }

        if ($feature === 'ai_credits') {
            $nav = $this->aiCreditsForNav($company);
            $remaining = $nav['unlimited'] ? null : (int) ($nav['remaining'] ?? 0);
            abort(403, __('ai_credits_insufficient', [
                'required' => max(1, $additionalUsage),
                'remaining' => $remaining ?? __('Unlimited'),
            ]));
        }

        abort(403, __('Plan limit reached for :feature.', ['feature' => $feature]));
    }

    /**
     * Plan cap for a feature, or null when unlimited (no active subscription, no limit row, or null limit_value).
     */
    public function planLimitValue(Company $company, string $feature): ?int
    {
        return $this->planLimitValueFromPlan($this->resolveActivePlan($company), $feature);
    }

    /**
     * @return list<array{key: string, label: string, icon: string}>
     */
    public function featureCatalog(): array
    {
        return [
            ['key' => 'projects', 'label' => __('Projects'), 'icon' => 'fa-diagram-project'],
            ['key' => 'users', 'label' => __('Users'), 'icon' => 'fa-users'],
            ['key' => 'forms', 'label' => __('Lead forms'), 'icon' => 'fa-rectangle-list'],
            ['key' => 'submissions', 'label' => __('Form submissions'), 'icon' => 'fa-inbox'],
            ['key' => 'widgets', 'label' => __('Widgets'), 'icon' => 'fa-code'],
            ['key' => 'ai_credits', 'label' => __('AI credits'), 'icon' => 'fa-wand-magic-sparkles'],
            ['key' => 'analytics', 'label' => __('Analytics'), 'icon' => 'fa-chart-line'],
            ['key' => 'marketing_hub', 'label' => __('Marketing hub'), 'icon' => 'fa-bullhorn'],
            ['key' => 'email_marketing', 'label' => __('Email marketing'), 'icon' => 'fa-envelope'],
            ['key' => 'reports', 'label' => __('Reports'), 'icon' => 'fa-file-lines'],
            ['key' => 'providers', 'label' => __('Providers'), 'icon' => 'fa-handshake'],
            ['key' => 'calendar', 'label' => __('Calendar'), 'icon' => 'fa-calendar-days'],
            ['key' => 'modules', 'label' => __('Modules'), 'icon' => 'fa-puzzle-piece'],
            ['key' => 'premium_tts', 'label' => __('Premium Nova voice (Gemini/OpenAI TTS)'), 'icon' => 'fa-microphone-lines'],
            ['key' => 'workspace_ai_agent', 'label' => __('Workspace AI agent (own API keys)'), 'icon' => 'fa-robot'],
            ['key' => 'hr', 'label' => __('HR & Payroll'), 'icon' => 'fa-people-group'],
        ];
    }

    /**
     * Keys from {@see featureCatalog()} — must stay aligned with FEATURE_KEYS.
     *
     * @return list<string>
     */
    public function featureCatalogKeys(): array
    {
        return array_column($this->featureCatalog(), 'key');
    }

    /**
     * Gemini / OpenAI TTS: requires platform API keys and an active paid subscription.
     */
    public function allowsPremiumTts(Company $company): bool
    {
        $plan = $this->resolveActivePlan($company);
        if ($plan === null) {
            return false;
        }

        if ((float) $plan->price_monthly <= 0) {
            return false;
        }

        return $this->featureEnabledFromPlan($plan, 'premium_tts');
    }

    public function featureLabel(string $key): string
    {
        foreach ($this->featureCatalog() as $row) {
            if ($row['key'] === $key) {
                return $row['label'];
            }
        }

        return $key;
    }

    /**
     * @return list<array{key: string, label: string, enabled: bool, quota: bool, used: ?int, limit: ?int, status: string}>
     */
    public function summarizePlanFeatures(?Plan $plan, ?Company $company = null): array
    {
        $limitsByKey = $plan?->limits->keyBy('feature_key') ?? collect();
        $rows = [];

        foreach ($this->featureCatalog() as $item) {
            $key = $item['key'];
            $limitRow = $limitsByKey->get($key);
            $limitValue = $limitRow?->limit_value;

            if ($limitRow !== null && $limitValue !== null && (int) $limitValue === 0) {
                $rows[] = [
                    'key' => $key,
                    'label' => $item['label'],
                    'enabled' => false,
                    'quota' => false,
                    'used' => null,
                    'limit' => 0,
                    'status' => __('Not included'),
                ];

                continue;
            }

            $isQuota = in_array($key, ['projects', 'users', 'forms', 'submissions', 'widgets', 'ai_credits', 'providers'], true);
            $limit = $limitValue === null ? null : (int) $limitValue;
            $used = ($company !== null && $isQuota) ? $this->currentUsage($company, $key) : null;

            if ($isQuota && $limit !== null) {
                $status = $company !== null
                    ? sprintf('%s / %s', number_format($used ?? 0), number_format($limit))
                    : number_format($limit);
            } elseif ($isQuota) {
                $status = $used !== null
                    ? sprintf('%s / %s', number_format($used), __('Unlimited'))
                    : __('Unlimited');
            } else {
                $status = __('Included');
            }

            $rows[] = [
                'key' => $key,
                'label' => $item['label'],
                'enabled' => true,
                'quota' => $isQuota,
                'used' => $used,
                'limit' => $limit,
                'status' => $status,
            ];
        }

        if ($plan !== null && $this->isAiGrowthIncluded($plan)) {
            $rows[] = [
                'key' => 'ai_growth_advisor',
                'label' => __('AI growth advisor'),
                'enabled' => true,
                'quota' => false,
                'used' => null,
                'limit' => null,
                'status' => __('Included'),
            ];
        }

        return $rows;
    }

    public function isAiGrowthIncluded(?Plan $plan): bool
    {
        if ($plan === null) {
            return true;
        }

        $plan->loadMissing('limits');
        $limit = $plan->limits->firstWhere('feature_key', 'ai_credits');
        if ($limit === null || $limit->limit_value === null) {
            return true;
        }

        return (int) $limit->limit_value > 0;
    }

    /**
     * @return array{show: bool, unlimited: bool, used: int, limit: ?int, remaining: ?int}
     */
    public function aiCreditsForNav(Company $company): array
    {
        if (! $this->isFeatureEnabled($company, 'ai_credits')) {
            return [
                'show' => false,
                'unlimited' => false,
                'used' => 0,
                'limit' => null,
                'remaining' => null,
            ];
        }

        $used = app(AiCreditUsageService::class)->usedThisMonth($company);
        $limit = $this->planLimitValue($company, 'ai_credits');
        if ($limit === null) {
            return [
                'show' => true,
                'unlimited' => true,
                'used' => $used,
                'limit' => null,
                'remaining' => null,
            ];
        }

        return [
            'show' => true,
            'unlimited' => false,
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
        ];
    }

    private function resolveActivePlan(Company $company): ?Plan
    {
        $key = (string) $company->id;
        if (array_key_exists($key, $this->resolvedPlans)) {
            return $this->resolvedPlans[$key];
        }

        $this->resolvedPlans[$key] = app(SubscriptionTrialService::class)->effectivePlan($company);

        return $this->resolvedPlans[$key];
    }

    private function featureEnabledFromPlan(?Plan $plan, string $feature): bool
    {
        if ($plan === null) {
            return true;
        }

        $limit = $this->limitRowForFeature($plan, $feature);
        if ($limit === null || $limit->limit_value === null) {
            return true;
        }

        return (int) $limit->limit_value > 0;
    }

    private function planLimitValueFromPlan(?Plan $plan, string $feature): ?int
    {
        if ($plan === null) {
            return null;
        }

        $limit = $this->limitRowForFeature($plan, $feature);
        if ($limit === null || $limit->limit_value === null) {
            return null;
        }

        $v = (int) $limit->limit_value;

        return $v > 0 ? $v : 0;
    }

    private function limitRowForFeature(Plan $plan, string $feature): ?PlanLimit
    {
        $plan->loadMissing('limits');

        return $plan->limits->firstWhere('feature_key', $feature);
    }

    private function currentUsage(Company $company, string $feature): int
    {
        return match ($feature) {
            'projects' => Project::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            'forms' => LeadForm::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            'users' => User::query()->where('company_id', $company->id)->workspaceStaff()->count(),
            'submissions' => FormSubmission::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            'widgets' => LeadForm::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            // Monthly cap: rows are stamped with period_start = first day of month (see AiCreditUsageService).
            'ai_credits' => app(AiCreditUsageService::class)->usedThisMonth($company),
            'providers' => Provider::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            default => 0,
        };
    }
}
