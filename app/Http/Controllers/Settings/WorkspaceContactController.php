<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkspaceContactController extends Controller
{
    use AuthorizesWorkspaceManagers;

    private function normalizeWebsite(?string $website): ?string
    {
        if ($website === null || trim($website) === '') {
            return null;
        }

        $w = trim($website);
        if (! preg_match('#^https?://#i', $w)) {
            $w = 'https://'.$w;
        }

        return substr($w, 0, 255);
    }

    public function edit(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        return view('settings.workspace-contact', [
            'company' => $company,
            'countries' => config('flowdesk_countries', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        if ($request->input('country') === '') {
            $request->merge(['country' => null]);
        }

        $data = $request->validate([
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'industry' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'size:2', Rule::in(array_keys(config('flowdesk_countries', [])))],
        ]);

        $country = isset($data['country']) && $data['country'] !== ''
            ? strtoupper((string) $data['country'])
            : null;

        $company->update([
            'contact_email' => $data['contact_email'] ?? null,
            'phone' => isset($data['phone']) && trim((string) $data['phone']) !== '' ? trim((string) $data['phone']) : null,
            'website' => $this->normalizeWebsite($data['website'] ?? null),
            'tax_id' => $data['tax_id'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'industry' => $data['industry'] ?? null,
            'country' => $country,
        ]);

        return redirect()->route('settings.workspace-contact')->with('status', __('Workspace contact saved.'));
    }
}
