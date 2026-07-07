<?php

namespace App\Http\Controllers;

use App\Enums\MarketplaceModuleCategory;
use App\Models\MarketplaceModule;
use App\Models\Plan;
use App\Services\MarketingRegionService;
use App\Services\MathCaptchaService;
use App\Services\PlanPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function home(): View
    {
        return view('marketing.home');
    }

    public function features(): View
    {
        return view('marketing.features');
    }

    public function about(): View
    {
        return view('marketing.about');
    }

    public function modules(Request $request, MarketingRegionService $regions): View
    {
        $resolved = $regions->resolve($request);
        $region = $resolved['region'];
        $country = $resolved['country'];
        $display = $resolved['currency'];

        $modules = $regions->publishedModulesQuery(
            $country,
            $region === 'global' ? null : $regions->countriesForRegion($region),
        )->get();

        $categoryFilter = (string) $request->query('category', '');
        if ($categoryFilter !== '' && MarketplaceModuleCategory::tryFrom($categoryFilter) === null) {
            $categoryFilter = '';
        }

        $categoryOptions = $modules
            ->pluck('category')
            ->unique()
            ->sortBy(fn (MarketplaceModuleCategory $category) => $category->sortOrder())
            ->values();

        if ($categoryFilter !== '') {
            $modules = $modules->filter(
                fn (MarketplaceModule $module) => $module->category->value === $categoryFilter,
            );
        }

        $grouped = $modules
            ->groupBy(fn (MarketplaceModule $module) => $module->category->value)
            ->sortKeysUsing(function (string $left, string $right): int {
                $leftOrder = MarketplaceModuleCategory::tryFrom($left)?->sortOrder() ?? 999;
                $rightOrder = MarketplaceModuleCategory::tryFrom($right)?->sortOrder() ?? 999;

                return $leftOrder <=> $rightOrder;
            });

        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];

        return view('marketing.modules', [
            'grouped' => $grouped,
            'categoryOptions' => $categoryOptions,
            'selectedCategory' => $categoryFilter,
            'displayCurrency' => $display,
            'supportedCurrencies' => $supported,
            'currencyLabels' => is_array(config('flowdesk.currency_labels')) ? config('flowdesk.currency_labels') : [],
            'selectedRegion' => $region,
            'selectedCountry' => $country,
            'marketingRegions' => $regions->regions(),
            'countryOptions' => $regions->countryOptionsForRegion($region),
            'regionService' => $regions,
        ]);
    }

    public function moduleShow(string $slug, Request $request, MarketingRegionService $regions): View
    {
        $module = MarketplaceModule::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $resolved = $regions->resolve($request);
        $country = $resolved['country'];

        if (! $module->isAvailableInCountry($country)) {
            abort(404);
        }

        $display = $resolved['currency'];

        return view('marketing.modules.show', [
            'module' => $module,
            'displayCurrency' => $display,
            'currencyLabels' => is_array(config('flowdesk.currency_labels')) ? config('flowdesk.currency_labels') : [],
            'selectedRegion' => $resolved['region'],
            'selectedCountry' => $country,
            'catalogQuery' => $request->only(['region', 'country', 'currency']),
        ]);
    }

    public function pricing(Request $request, PlanPricingService $planPricing): View
    {
        $supported = config('flowdesk.supported_currencies', ['USD']);
        $supported = is_array($supported) ? $supported : ['USD'];
        $display = strtoupper((string) $request->query('currency', 'USD'));
        if (! in_array($display, $supported, true)) {
            $display = 'USD';
        }

        $plans = Plan::query()->with(['limits', 'periodPrices'])->orderBy('name')->get();
        $planRows = array_reverse($planPricing->buildPlanRows($plans, $display));

        $currencyLabels = config('flowdesk.currency_labels', []);
        $trialPlanSlug = (string) config('flowdesk.trial_plan_slug', 'pro');

        return view('marketing.pricing', [
            'planRows' => $planRows,
            'displayCurrency' => $display,
            'supportedCurrencies' => $supported,
            'currencyLabels' => is_array($currencyLabels) ? $currencyLabels : [],
            'trialDays' => max(1, (int) config('flowdesk.trial_days', 14)),
            'trialPlanName' => $plans->firstWhere('slug', $trialPlanSlug)?->name
                ?? ucfirst($trialPlanSlug),
        ]);
    }

    private const CONTACT_CAPTCHA_CONTEXT = 'marketing-contact';

    public function contact(MathCaptchaService $mathCaptcha): View
    {
        return view('marketing.contact', [
            'captcha' => $mathCaptcha->generate(self::CONTACT_CAPTCHA_CONTEXT),
            'contactEmail' => config('flowdesk.contact_inbox_email'),
        ]);
    }

    public function terms(): View
    {
        return view('marketing.terms');
    }

    public function privacy(): View
    {
        return view('marketing.privacy');
    }

    public function cookies(): View
    {
        return view('marketing.cookies');
    }

    public function legal(): View
    {
        return view('marketing.legal');
    }

    public function contactStore(Request $request, MathCaptchaService $mathCaptcha): RedirectResponse
    {
        $captchaError = $mathCaptcha->validate($request, self::CONTACT_CAPTCHA_CONTEXT);
        if ($captchaError !== null) {
            return back()
                ->withErrors(['_captcha_answer' => $captchaError])
                ->withInput($request->except('_captcha_token', '_captcha_answer'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('flowdesk.contact_inbox_email');
        $lines = [
            'Flowqil contact form',
            'Name: '.$data['name'],
            'Email: '.$data['email'],
            'Company: '.($data['company'] ?? '—'),
            '',
            $data['message'],
        ];
        $body = implode("\n", $lines);

        Log::info('marketing.contact', [
            'email' => $data['email'],
            'name' => $data['name'],
        ]);

        if (is_string($to) && $to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw($body, function ($message) use ($to, $data): void {
                    $message->to($to)->subject(__('Contact form').': '.$data['name']);
                });
            } catch (\Throwable $e) {
                Log::warning('marketing.contact.mail_failed', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('marketing.contact')->with('status', __('Thanks — we received your message and will get back to you soon.'));
    }
}
