<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\UsageTracking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PlatformReportService
{
    /**
     * @return array<string, int|string|float>
     */
    public function snapshotAllTime(): array
    {
        return [
            'companies_total' => Company::query()->count(),
            'workspace_users_total' => User::query()->whereNotNull('company_id')->workspaceStaff()->count(),
            'projects_total' => Project::query()->withoutGlobalScopes()->count(),
            'invoices_total' => Invoice::query()->withoutGlobalScope('tenant')->count(),
            'payments_completed_count' => Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Completed)->count(),
            'payments_completed_minor' => (int) Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Completed)->sum('amount'),
            'ai_credits_all_time' => (int) UsageTracking::query()->withoutGlobalScopes()->where('metric', 'ai_credits')->sum('value'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function snapshotPeriod(Carbon $from, Carbon $to): array
    {
        return [
            'new_companies' => Company::query()->whereBetween('created_at', [$from, $to])->count(),
            'payments_completed_count' => Payment::query()->withoutGlobalScopes()
                ->where('status', PaymentStatus::Completed)
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'payments_completed_minor' => (int) Payment::query()->withoutGlobalScopes()
                ->where('status', PaymentStatus::Completed)
                ->whereBetween('created_at', [$from, $to])
                ->sum('amount'),
            'ai_credits' => (int) UsageTracking::query()->withoutGlobalScopes()
                ->where('metric', 'ai_credits')
                ->whereBetween('created_at', [$from, $to])
                ->sum('value'),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Company>
     */
    public function companiesTable(Carbon $from, Carbon $to, int $perPage = 25): LengthAwarePaginator
    {
        $aiSub = UsageTracking::query()
            ->withoutGlobalScopes()
            ->selectRaw('company_id, SUM(value) as ai_credits_period')
            ->where('metric', 'ai_credits')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('company_id');

        return Company::query()
            ->with('plan')
            ->leftJoinSub($aiSub, 'ai_agg', 'companies.id', '=', 'ai_agg.company_id')
            ->select('companies.*')
            ->addSelect(DB::raw('COALESCE(ai_agg.ai_credits_period, 0) as ai_credits_period'))
            ->withCount([
                'users as workspace_staff_count' => fn ($q) => $q->workspaceStaff(),
            ])
            ->withCount(['projects', 'invoices', 'clients'])
            ->orderBy('companies.name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
