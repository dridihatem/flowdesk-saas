<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmtpController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        $settings = $company->settings;
        $smtp = $settings?->smtp ?? [];

        return view('settings.smtp', compact('smtp'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $data = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,null'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $company->settings()->firstOrCreate();
        $smtp = array_filter([
            'host' => $data['host'] ?? null,
            'port' => $data['port'] ?? null,
            'encryption' => $data['encryption'] ?? null,
            'username' => $data['username'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'from_name' => $data['from_name'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($data['password'])) {
            $smtp['password'] = $data['password'];
        } else {
            $existing = $settings->smtp ?? [];
            if (isset($existing['password'])) {
                $smtp['password'] = $existing['password'];
            }
        }

        $settings->update(['smtp' => $smtp]);

        return redirect()->route('settings.smtp')->with('status', __('SMTP settings saved.'));
    }
}
