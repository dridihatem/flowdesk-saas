<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceApiConnectController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function show(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $subdomain = $company->subdomain;
        if ($subdomain && ! str_starts_with($host, $subdomain.'.')) {
            $tenantHost = $subdomain.'.'.$host;
        } else {
            $tenantHost = $host;
        }
        $workspaceApiBase = $scheme.'://'.$tenantHost.'/api/v1/workspace';
        $revealedToken = $request->session()->pull('flowdesk_company_api_token_plain');

        return view('settings.api-connect', [
            'baseUrl' => rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/'),
            'workspaceApiBase' => $workspaceApiBase,
            'subdomain' => $subdomain,
            'hasApiToken' => $company->api_token_hash !== null,
            'apiTokenHint' => $company->api_token_hint,
            'revealedToken' => $revealedToken,
            'apiTokenPlain' => $revealedToken ?: $company->apiTokenPlain(),
        ]);
    }

    public function regenerateToken(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $plain = $user->company->regenerateApiToken();
        $request->session()->flash('flowdesk_company_api_token_plain', $plain);

        return redirect()->route('settings.api-connect')
            ->with('status', __('workspace_api_token_regenerated'));
    }
}
