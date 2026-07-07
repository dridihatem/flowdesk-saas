<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Models\Company;
use App\Services\MailchimpStatusService;
use App\Services\MarketingIntegrationConfigService;
use App\Services\TwilioSmsTestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class MarketingIntegrationsController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function __construct(
        private MarketingIntegrationConfigService $integration,
        private MailchimpStatusService $mailchimp,
        private TwilioSmsTestService $twilioSms
    ) {}

    public function edit(Request $request): View
    {
        $this->authorizeWorkspaceManagers($request);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);
        $form = $this->integration->toFormArray($company);

        return view('settings.marketing-integrations', compact('form', 'company'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeWorkspaceManagers($request);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);

        $data = $request->validate([
            'campaign_email' => ['required', 'string', 'in:app_default,tenant_smtp,sendgrid'],
            'sendgrid_api_key' => ['nullable', 'string', 'max:2048'],
            'clear_sendgrid_api_key' => ['nullable', 'boolean'],
            'mailchimp_api_key' => ['nullable', 'string', 'max:2048'],
            'mailchimp_server_prefix' => ['nullable', 'string', 'max:64'],
            'mailchimp_list_id' => ['nullable', 'string', 'max:64'],
            'clear_mailchimp_api_key' => ['nullable', 'boolean'],
            'twilio_account_sid' => ['nullable', 'string', 'max:64'],
            'twilio_from' => ['nullable', 'string', 'max:32'],
            'twilio_auth_token' => ['nullable', 'string', 'max:128'],
            'clear_twilio_token' => ['nullable', 'boolean'],
        ]);
        $data['clear_sendgrid_api_key'] = (bool) ($request->boolean('clear_sendgrid_api_key') ?? false);
        $data['clear_mailchimp_api_key'] = (bool) ($request->boolean('clear_mailchimp_api_key') ?? false);
        $data['clear_twilio_token'] = (bool) ($request->boolean('clear_twilio_token') ?? false);

        $this->integration->saveFromRequest($company, $data);

        return redirect()
            ->route('settings.marketing-integrations')
            ->with('status', __('marketing_integrations_saved'));
    }

    public function testMailchimp(Request $request): RedirectResponse
    {
        $this->authorizeWorkspaceManagers($request);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);
        $r = $this->integration->getResolved($company);
        $k = $r['mailchimp']['api_key'] ?? '';
        $p = (string) ($r['mailchimp']['server_prefix'] ?? '');
        if ($k === '' || $p === '') {
            return back()->withErrors(['mailchimp' => __('mailchimp_test_missing')]);
        }
        if ($this->mailchimp->ping($k, $p)) {
            return back()->with('status', __('mailchimp_test_ok'));
        }

        return back()->withErrors(['mailchimp' => __('mailchimp_test_fail')]);
    }

    public function testTwilio(Request $request): RedirectResponse
    {
        $this->authorizeWorkspaceManagers($request);
        $request->validate([
            'to' => ['required', 'string', 'max:32'],
        ]);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);
        try {
            $this->twilioSms->sendTest($company, (string) $request->input('to'));
        } catch (Throwable $e) {
            Log::warning('twilio.test_failed', ['message' => $e->getMessage()]);

            return back()->withErrors(['twilio' => $e->getMessage()]);
        }

        return back()->with('status', __('twilio_test_ok'));
    }
}
