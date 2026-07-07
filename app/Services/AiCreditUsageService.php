<?php

namespace App\Services;

use App\Models\Company;
use App\Models\UsageTracking;

class AiCreditUsageService
{
    public const TASK_ASSISTANT = 'assistant';

    public const TASK_REPORT_COUNSEL = 'report_counsel';

    public const TASK_PROJECT_WORKFLOW = 'project_workflow';

    public const TASK_PROJECT_EXAMPLE = 'project_example_workspace';

    public const TASK_EMAIL_TEMPLATE = 'email_template';

    public const TASK_EMAIL_CAMPAIGN = 'email_campaign_content';

    /**
     * Sum of AI credits consumed in the current calendar month (matches {@see record} period).
     */
    public function usedThisMonth(Company $company): int
    {
        return (int) $company->usageTracking()
            ->where('metric', 'ai_credits')
            ->whereDate('period_start', now()->startOfMonth()->toDateString())
            ->sum('value');
    }

    public function remainingThisMonth(Company $company, ?int $planLimit): ?int
    {
        if ($planLimit === null) {
            return null;
        }

        return max(0, $planLimit - $this->usedThisMonth($company));
    }

    public function record(Company $company, int $credits = 1): void
    {
        $credits = max(0, $credits);
        if ($credits === 0) {
            return;
        }
        UsageTracking::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'metric' => 'ai_credits',
            'value' => $credits,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Flat credit cost for a task (and optional variant such as assistant mode).
     */
    public function creditsForTask(string $task, ?string $variant = null): int
    {
        $tasks = config('flowdesk.ai_task_credits', []);
        if (! is_array($tasks)) {
            return 1;
        }

        $entry = $tasks[$task] ?? null;

        if (is_array($entry)) {
            $modes = $entry['modes'] ?? [];
            if ($variant !== null && $variant !== '' && isset($modes[$variant])) {
                return max(1, (int) $modes[$variant]);
            }

            return max(1, (int) ($entry['default'] ?? 1));
        }

        if (is_numeric($entry)) {
            return max(1, (int) $entry);
        }

        return 1;
    }

    /**
     * Bill the workspace for one AI task.
     */
    public function recordForTask(Company $company, string $task, ?string $variant = null): int
    {
        $credits = $this->creditsForTask($task, $variant);
        $this->record($company, $credits);

        return $credits;
    }

    /**
     * @return array<string, int>
     */
    public function assistantModeCosts(): array
    {
        $tasks = config('flowdesk.ai_task_credits', []);
        $assistant = is_array($tasks) ? ($tasks[self::TASK_ASSISTANT] ?? []) : [];
        $modes = is_array($assistant) ? ($assistant['modes'] ?? []) : [];
        $default = is_array($assistant) ? (int) ($assistant['default'] ?? 1) : 1;
        $out = [];
        foreach ($modes as $mode => $cost) {
            $out[(string) $mode] = max(1, (int) $cost);
        }
        $out['default'] = max(1, $default);

        return $out;
    }

    /**
     * Flat credit costs exposed to views (assistant modes + standalone tasks).
     *
     * @return array<string, int>
     */
    public function publicTaskCosts(): array
    {
        return array_merge($this->assistantModeCosts(), [
            self::TASK_REPORT_COUNSEL => $this->creditsForTask(self::TASK_REPORT_COUNSEL),
            self::TASK_PROJECT_WORKFLOW => $this->creditsForTask(self::TASK_PROJECT_WORKFLOW),
            self::TASK_PROJECT_EXAMPLE => $this->creditsForTask(self::TASK_PROJECT_EXAMPLE),
            self::TASK_EMAIL_TEMPLATE => $this->creditsForTask(self::TASK_EMAIL_TEMPLATE),
            self::TASK_EMAIL_CAMPAIGN => $this->creditsForTask(self::TASK_EMAIL_CAMPAIGN),
        ]);
    }
}
