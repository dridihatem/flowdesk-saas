<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Services\CompanyThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingTaxSettingsController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request, CompanyThemeService $themes): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $settings = $themes->ensureSettings($company);
        $billing = is_array($settings->billing) ? $settings->billing : [];

        $stampMinor = (int) ($billing['fiscal_stamp_minor'] ?? 0);
        $dc = strtoupper((string) ($company->default_currency ?? 'USD'));

        return view('settings.billing-tax', [
            'company' => $company,
            'vat_percent' => old('vat_percent', $billing['vat_percent'] ?? ''),
            'fiscal_stamp_enabled' => old('fiscal_stamp_enabled', filter_var($billing['fiscal_stamp_enabled'] ?? false, FILTER_VALIDATE_BOOL) ? '1' : '0'),
            'fiscal_stamp_amount' => old('fiscal_stamp_amount', flowdesk_major_amount_for_input($stampMinor, $dc)),
        ]);
    }

    public function update(Request $request, CompanyThemeService $themes): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $data = $request->validate([
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fiscal_stamp_amount' => ['nullable', 'string', 'max:32'],
        ]);

        $dc = strtoupper((string) ($company->default_currency ?? 'USD'));
        $stampMinor = null;
        if (isset($data['fiscal_stamp_amount']) && trim((string) $data['fiscal_stamp_amount']) !== '') {
            $stampMinor = flowdesk_decimal_to_minor(trim((string) $data['fiscal_stamp_amount']), $dc);
        }

        $settings = $themes->ensureSettings($company);
        $prev = is_array($settings->billing) ? $settings->billing : [];
        $settings->billing = array_merge($prev, [
            'vat_percent' => isset($data['vat_percent']) && $data['vat_percent'] !== '' ? (float) $data['vat_percent'] : null,
            'fiscal_stamp_enabled' => $request->boolean('fiscal_stamp_enabled'),
            'fiscal_stamp_minor' => $stampMinor,
        ]);
        $settings->save();

        return redirect()->route('settings.billing-tax')->with('status', __('Billing & tax settings saved.'));
    }
}
