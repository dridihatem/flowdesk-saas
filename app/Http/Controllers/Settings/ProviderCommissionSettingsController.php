<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Models\Client;
use App\Services\CompanyThemeService;
use App\Services\ProviderCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderCommissionSettingsController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request, CompanyThemeService $themes, ProviderCommissionService $commissions): View
    {
        $user = $this->authorizeWorkspaceManagers($request);

        $company = $user->company;
        $settings = $themes->ensureSettings($company);
        $raw = is_array($settings->provider_commission_client_tiers) ? $settings->provider_commission_client_tiers : [];
        $normalized = $commissions->normalizeWorkspaceClientTiers($raw);

        $rows = array_fill(0, 5, ['from_clients' => '', 'to_clients' => '', 'percent' => '']);
        foreach ($normalized as $i => $tier) {
            if ($i >= 5) {
                break;
            }
            $rows[$i] = [
                'from_clients' => (string) $tier['from_clients'],
                'to_clients' => $tier['to_clients'] !== null ? (string) $tier['to_clients'] : '',
                'percent' => (string) round($tier['rate'] * 100, 4),
            ];
        }

        $clientCount = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $previewRate = $commissions->rateForWorkspaceClientTiers($raw, $clientCount);

        return view('settings.provider-commissions', [
            'company' => $company,
            'tierRows' => $rows,
            'clientCount' => $clientCount,
            'previewRate' => $previewRate,
        ]);
    }

    public function update(Request $request, CompanyThemeService $themes, ProviderCommissionService $commissions): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);

        $request->validate([
            'tiers' => ['nullable', 'array'],
        ]);

        $built = [];
        foreach ($request->input('tiers', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $from = $row['from_clients'] ?? null;
            $percent = $row['percent'] ?? null;
            $to = $row['to_clients'] ?? null;
            $fromEmpty = $from === null || $from === '';
            $pctEmpty = $percent === null || $percent === '';
            $toEmpty = $to === null || $to === '';
            if ($fromEmpty && $toEmpty && $pctEmpty) {
                continue;
            }
            if ($fromEmpty || $pctEmpty) {
                return redirect()->back()->withErrors([
                    'tiers' => __('Each non-empty tier needs a minimum client count and a commission percentage.'),
                ])->withInput();
            }
            $fromInt = (int) $from;
            $toInt = $toEmpty ? null : (int) $to;
            $pctFloat = (float) $percent;
            if ($fromInt < 0 || $toInt !== null && $toInt < $fromInt || $pctFloat < 0 || $pctFloat > 100) {
                return redirect()->back()->withErrors([
                    'tiers' => __('Check client ranges (min ≤ max) and percentages between 0 and 100.'),
                ])->withInput();
            }
            $built[] = [
                'from_clients' => $fromInt,
                'to_clients' => $toInt,
                'rate' => round($pctFloat / 100, 6),
            ];
        }

        $normalized = $commissions->normalizeWorkspaceClientTiers($built);
        $settings = $themes->ensureSettings($user->company);
        $settings->provider_commission_client_tiers = $normalized === [] ? null : $normalized;
        $settings->save();

        return redirect()->route('settings.provider-commissions')->with('status', __('Provider commission rules saved.'));
    }
}
