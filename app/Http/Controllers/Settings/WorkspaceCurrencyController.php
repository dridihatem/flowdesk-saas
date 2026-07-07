<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceCurrencyController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $currencyOptions = flowdesk_currency_select_options($company->default_currency);

        return view('settings.workspace-currency', compact('company', 'currencyOptions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $data = $request->validate([
            'default_currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency)],
        ]);

        $company->update([
            'default_currency' => strtoupper($data['default_currency']),
        ]);

        return redirect()->route('settings.workspace-currency')->with('status', __('Default currency saved.'));
    }
}
