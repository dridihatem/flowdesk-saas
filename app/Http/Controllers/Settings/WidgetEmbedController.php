<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WidgetEmbedController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function show(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);

        $company = $user->company;
        $baseUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
        $revealedToken = $request->session()->pull('flowdesk_company_api_token_plain');

        return view('settings.widget-embed', [
            'baseUrl' => $baseUrl,
            'hasApiToken' => $company->api_token_hash !== null,
            'apiTokenHint' => $company->api_token_hint,
            'revealedToken' => $revealedToken,
            // Stored (encrypted) token: always available for display and snippets;
            // null for legacy tokens generated before encryption was kept.
            'apiTokenPlain' => $revealedToken ?: $company->apiTokenPlain(),
        ]);
    }

    public function regenerateToken(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $plain = $user->company->regenerateApiToken();
        $request->session()->flash('flowdesk_company_api_token_plain', $plain);

        return redirect()->route('settings.widget-embed')
            ->with('status', __('New API token generated. Copy it now — it will not be shown again.'));
    }
}
