<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\NegotiationStatus;
use App\Enums\ProposalStatus;
use App\Models\FormSubmission;
use App\Models\Invoice;
use App\Models\Negotiation;
use App\Models\Plan;
use App\Models\Proposal;
use App\Models\UsageTracking;
use App\Services\AiCreditUsageService;
use App\Services\CompanyGrowthAiService;
use App\Services\PlanLimitService;
use App\Services\PlanPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __invoke(
        Request $request,
        PlanPricingService $planPricing,
        PlanLimitService $planLimits,
        CompanyGrowthAiService $growthAi,
    ): View {
        $user = $request->user();
        abort_if(! $user->hasRole('company_admin'), 403);
        $company = $user->company;
        abort_if(! $company, 403);

        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];
        $displayCurrency = strtoupper((string) $request->query('currency', $company->default_currency ?? 'USD'));
        if (! in_array($displayCurrency, $supported, true)) {
            $displayCurrency = in_array($company->default_currency ?? '', $supported, true)
                ? (string) $company->default_currency
                : 'USD';
        }

        $allPlans = Plan::query()->with(['limits', 'periodPrices'])->orderBy('name')->get();
        $planPricingRows = array_reverse($planPricing->buildPlanRows($allPlans, $displayCurrency));
        $currencyLabels = config('flowdesk.currency_labels', []);
        $currencyLabels = is_array($currencyLabels) ? $currencyLabels : [];

        $subscription = $company->subscriptions()->whereIn('status', ['active', 'trialing'])->with('plan.limits')->latest('id')->first();
        $company->loadMissing(['plan.limits']);
        $plan = $company->plan ?? $subscription?->plan;
        if ($plan && ! $plan->relationLoaded('limits')) {
            $plan->load('limits');
        }

        $invoiceRevenue = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->where('status', InvoiceStatus::Paid)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $commissionTotal = Negotiation::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', NegotiationStatus::Accepted)
            ->whereNotNull('commission_amount_minor')
            ->where('created_at', '>=', now()->subDays(365))
            ->sum('commission_amount_minor');

        $proposalPipeline = Proposal::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [ProposalStatus::Sent, ProposalStatus::Draft])
            ->count();

        $aiCreditsUsed = UsageTracking::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('metric', 'ai_credits')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('value');

        $formSubmissionsMonth = FormSubmission::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $payPerUse = Config::get('flowdesk.pay_per_use', []);
        $aiCreditPriceMinor = (int) ($payPerUse['ai_credit_price_minor'] ?? 1);
        $payPerUseEstimate = (int) $aiCreditsUsed * $aiCreditPriceMinor;

        $planAddons = is_array($plan?->addons) ? $plan->addons : [];
        $stripePortalAvailable = Config::get('services.stripe.secret') !== null && Config::get('services.stripe.secret') !== '';

        $subscriptionFeatureRows = $planLimits->summarizePlanFeatures($plan, $company);
        $aiCredits = app(AiCreditUsageService::class);
        $aiGrowthModules = collect($growthAi->modules())->map(function (array $module) use ($growthAi, $company, $aiCredits) {
            $creditCost = $aiCredits->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, $module['mode']);

            return array_merge($module, [
                'context' => $growthAi->buildContext($company, $module['mode']),
                'credit_cost' => $creditCost,
            ]);
        })->all();
        $minGrowthCost = collect($aiGrowthModules)->min('credit_cost') ?: 100;
        $aiGrowthAvailable = $planLimits->isFeatureEnabled($company, 'ai_credits')
            && $planLimits->allows($company, 'ai_credits', (int) $minGrowthCost);

        return view('billing.index', compact(
            'subscription',
            'plan',
            'invoiceRevenue',
            'commissionTotal',
            'proposalPipeline',
            'aiCreditsUsed',
            'formSubmissionsMonth',
            'payPerUseEstimate',
            'aiCreditPriceMinor',
            'planAddons',
            'stripePortalAvailable',
            'planPricingRows',
            'displayCurrency',
            'supported',
            'currencyLabels',
            'subscriptionFeatureRows',
            'aiGrowthAvailable',
            'aiGrowthModules',
        ));
    }
}
