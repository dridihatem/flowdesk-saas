<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        $security = $company->settings?->security ?? [];
        $allowedIpsText = implode("\n", $security['allowed_ips'] ?? []);

        return view('settings.security', compact('allowedIpsText'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $data = $request->validate([
            'allowed_ips' => ['nullable', 'string', 'max:5000'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', (string) ($data['allowed_ips'] ?? '')) ?: [];
        $ips = array_values(array_filter(array_map('trim', $lines), fn ($v) => $v !== ''));

        $settings = $company->settings()->firstOrCreate();
        $prev = $settings->security ?? [];
        $settings->update([
            'security' => array_merge($prev, [
                'allowed_ips' => $ips,
            ]),
        ]);

        return redirect()->route('settings.security')->with('status', __('Security settings saved.'));
    }
}
