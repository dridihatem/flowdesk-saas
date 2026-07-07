<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Models\Company;
use App\Services\WorkspaceAiConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceAiAgentController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function __construct(
        private WorkspaceAiConfigService $workspaceAi,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorizeWorkspaceManagers($request);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);

        $form = $this->workspaceAi->toFormArray($company);

        return view('settings.ai-agent', compact('form', 'company'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeWorkspaceManagers($request);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'ai_provider' => ['required', 'string', 'in:auto,anthropic,openai,google'],
            'openai_api_key' => ['nullable', 'string', 'max:2048'],
            'anthropic_api_key' => ['nullable', 'string', 'max:2048'],
            'google_api_key' => ['nullable', 'string', 'max:2048'],
            'openai_model' => ['nullable', 'string', 'max:128'],
            'claude_model' => ['nullable', 'string', 'max:128'],
            'gemini_model' => ['nullable', 'string', 'max:128'],
            'clear_openai_api_key' => ['nullable', 'boolean'],
            'clear_anthropic_api_key' => ['nullable', 'boolean'],
            'clear_google_api_key' => ['nullable', 'boolean'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['clear_openai_api_key'] = $request->boolean('clear_openai_api_key');
        $data['clear_anthropic_api_key'] = $request->boolean('clear_anthropic_api_key');
        $data['clear_google_api_key'] = $request->boolean('clear_google_api_key');

        $this->workspaceAi->saveFromRequest($company, $data);

        return redirect()
            ->route('settings.ai-agent')
            ->with('status', __('workspace_ai_agent_saved'));
    }
}
