<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingAudience;
use App\Models\EmailMarketingAudienceContact;
use App\Models\EmailMarketingCampaign;
use App\Models\EmailMarketingRecipientDelivery;
use App\Models\EmailMarketingTemplate;
use App\Services\EmailMarketingCampaignSendService;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use App\Support\EmailMarketingTemplateLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! $request->user()->company, 403);

        $campaigns = EmailMarketingCampaign::query()
            // Explicit filter: do not rely solely on the tenant global scope.
            ->where('company_id', $request->user()->company_id)
            ->with(['audience' => fn ($q) => $q->withCount('contacts')])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('email-marketing.campaigns.index', compact('campaigns'));
    }

    public function create(Request $request, PlatformLlmRouter $llm, PlanLimitService $planLimits): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $aiAvailable = $llm->isAvailable($company) && $planLimits->isFeatureEnabled($company, 'ai_credits');

        $audiences = $this->audiencesList($request);
        $templates = EmailMarketingTemplate::query()->orderBy('name')->get();
        $modelTemplates = EmailMarketingTemplateLibrary::models();
        $audId = old('audience_id', $request->query('audience_id'));
        $audienceContacts = $this->loadAudienceContacts($audId !== null && $audId !== '' ? (string) $audId : null);
        $selectedContactIds = (array) old('contact_ids', []);

        return view('email-marketing.campaigns.create', compact('audiences', 'templates', 'modelTemplates', 'audienceContacts', 'selectedContactIds', 'aiAvailable'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $this->validatedCampaign($request, $company->id, null);

        $bodyHtml = $data['body_html'];
        if (! empty($data['workspace_template_id'])) {
            $tpl = EmailMarketingTemplate::query()->whereKey($data['workspace_template_id'])->first();
            if ($tpl) {
                $bodyHtml = $tpl->body_html ?? $bodyHtml;
            }
        }

        $campaign = EmailMarketingCampaign::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'status' => 'draft',
            'subject' => $data['subject'],
            'body_html' => $bodyHtml,
            'audience_id' => $data['audience_id'],
            'recipient_scope' => $data['recipient_scope'] ?? 'all',
        ]);
        $this->syncSelectedContacts($campaign, $data);

        return redirect()
            ->route('email-marketing.campaigns.index')
            ->with('status', __('email_marketing_campaign_saved'));
    }

    public function edit(Request $request, EmailMarketingCampaign $campaign, PlatformLlmRouter $llm, PlanLimitService $planLimits): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        $this->authorizeCampaign($request, $campaign);
        $aiAvailable = $llm->isAvailable($company) && $planLimits->isFeatureEnabled($company, 'ai_credits');
        $campaign->load('selectedAudienceContacts');

        $audiences = $this->audiencesList($request);
        $templates = EmailMarketingTemplate::query()->orderBy('name')->get();
        $modelTemplates = EmailMarketingTemplateLibrary::models();
        $aid = old('audience_id', (string) ($campaign->audience_id ?? $request->query('audience_id', '')));
        $aid = $aid === '' ? null : $aid;
        $audienceContacts = $this->loadAudienceContacts($aid);
        $selectedContactIds = $this->selectedIdsForForm($request, $campaign);

        return view('email-marketing.campaigns.edit', compact('campaign', 'audiences', 'templates', 'modelTemplates', 'audienceContacts', 'selectedContactIds', 'aiAvailable'));
    }

    public function show(Request $request, EmailMarketingCampaign $campaign): View
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeCampaign($request, $campaign);
        $campaign->load('audience');

        $base = EmailMarketingRecipientDelivery::query()
            ->where('email_marketing_campaign_id', $campaign->id)
            ->where('kind', EmailMarketingRecipientDelivery::KIND_MASS);

        $sent = (clone $base)->count();
        $opened = (clone $base)->whereNotNull('first_opened_at')->count();
        $stats = [
            'sent' => $sent,
            'opened' => $opened,
            'rate' => $sent > 0 ? round(100 * $opened / $sent, 1) : null,
        ];

        $deliveries = EmailMarketingRecipientDelivery::query()
            ->where('email_marketing_campaign_id', $campaign->id)
            ->where('kind', EmailMarketingRecipientDelivery::KIND_MASS)
            ->orderBy('recipient_email')
            ->paginate(30);

        return view('email-marketing.campaigns.show', compact('campaign', 'stats', 'deliveries'));
    }

    public function sendSample(Request $request, EmailMarketingCampaign $campaign, EmailMarketingCampaignSendService $sendService): RedirectResponse
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeCampaign($request, $campaign);

        $request->validate([
            'sample_to' => ['required', 'string', 'max:255', 'email'],
        ]);

        try {
            $sendService->sendSample($campaign, (string) $request->input('sample_to'));
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('status', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('status', __('email_marketing_sample_sent'));
    }

    public function update(Request $request, EmailMarketingCampaign $campaign): RedirectResponse
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeCampaign($request, $campaign);

        if ($campaign->status === 'sent') {
            return redirect()
                ->route('email-marketing.campaigns.edit', $campaign)
                ->with('status', __('email_marketing_campaign_sent_readonly'));
        }

        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $this->validatedCampaign($request, $company->id, $campaign);

        $bodyHtml = $data['body_html'];
        if (! empty($data['workspace_template_id'])) {
            $tpl = EmailMarketingTemplate::query()->whereKey($data['workspace_template_id'])->first();
            if ($tpl) {
                $bodyHtml = $tpl->body_html ?? $bodyHtml;
            }
        }

        $campaign->update([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body_html' => $bodyHtml,
            'audience_id' => $data['audience_id'],
            'recipient_scope' => $data['recipient_scope'] ?? 'all',
        ]);
        $this->syncSelectedContacts($campaign, $data);

        return redirect()
            ->route('email-marketing.campaigns.index')
            ->with('status', __('email_marketing_campaign_saved'));
    }

    public function destroy(Request $request, EmailMarketingCampaign $campaign): RedirectResponse
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeCampaign($request, $campaign);

        if ($campaign->status === 'sent') {
            return redirect()
                ->route('email-marketing.campaigns.index')
                ->with('status', __('email_marketing_campaign_cannot_delete_sent'));
        }

        $campaign->delete();

        return redirect()
            ->route('email-marketing.campaigns.index')
            ->with('status', __('email_marketing_campaign_deleted'));
    }

    public function send(Request $request, EmailMarketingCampaign $campaign, EmailMarketingCampaignSendService $sendService): RedirectResponse
    {
        abort_if(! $request->user()->company, 403);
        $this->authorizeCampaign($request, $campaign);

        try {
            $sendService->send($campaign);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('email-marketing.campaigns.edit', $campaign)
                ->with('status', $e->getMessage());
        }

        return redirect()
            ->route('email-marketing.campaigns.index')
            ->with('status', __('email_marketing_campaign_sent_ok'));
    }

    private function authorizeCampaign(Request $request, EmailMarketingCampaign $campaign): void
    {
        $companyId = $request->user()?->company_id;
        abort_if(! $companyId || (string) $campaign->company_id !== (string) $companyId, 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSelectedContacts(EmailMarketingCampaign $campaign, array $data): void
    {
        if (($data['recipient_scope'] ?? 'all') === 'selected' && ! empty($data['contact_ids']) && is_array($data['contact_ids'])) {
            $campaign->selectedAudienceContacts()->sync($data['contact_ids']);
        } else {
            $campaign->selectedAudienceContacts()->sync([]);
        }
    }

    private function loadAudienceContacts(?string $audienceId)
    {
        if ($audienceId === null || $audienceId === '') {
            return collect();
        }

        return EmailMarketingAudienceContact::query()
            ->where('audience_id', $audienceId)
            ->orderBy('email')
            ->get();
    }

    private function selectedIdsForForm(Request $request, EmailMarketingCampaign $campaign): array
    {
        if (old('contact_ids') !== null) {
            $raw = (array) old('contact_ids', []);

            return array_map('strval', $raw);
        }
        if ($campaign->recipient_scope === 'selected') {
            $campaign->load('selectedAudienceContacts');

            return $campaign->selectedAudienceContacts->pluck('id')->map(fn ($id) => (string) $id)->all();
        }

        return [];
    }

    private function audiencesList(Request $request)
    {
        return EmailMarketingAudience::query()
            ->withCount('contacts')
            ->orderBy('name')
            ->get();
    }

    private function validatedCampaign(Request $request, string $companyId, ?EmailMarketingCampaign $existing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'body_html' => ['required', 'string'],
            'audience_id' => [
                'nullable',
                'string',
                Rule::exists('email_marketing_audiences', 'id')->where('company_id', $companyId),
            ],
            'workspace_template_id' => [
                'nullable',
                'string',
                Rule::exists('email_marketing_templates', 'id')->where('company_id', $companyId),
            ],
            'recipient_scope' => ['required', 'string', 'in:all,selected'],
            'contact_ids' => [
                'nullable',
                'array',
            ],
            'contact_ids.*' => [
                'string',
                Rule::exists('email_marketing_audience_contacts', 'id')->where(function ($q) use ($request) {
                    $q->where('audience_id', $request->input('audience_id'));
                }),
            ],
        ]);

        $data['audience_id'] = $data['audience_id'] ?? null;
        $data['workspace_template_id'] = $data['workspace_template_id'] ?? null;
        if (($data['recipient_scope'] ?? 'all') === 'selected') {
            if (empty($data['audience_id'])) {
                $request->validate(
                    ['audience_id' => 'required|exists:email_marketing_audiences,id,company_id,'.$companyId],
                    ['audience_id.required' => __('email_marketing_recipients_need_audience')]
                );
            }
            if (empty($data['contact_ids']) || ! is_array($data['contact_ids'])) {
                throw ValidationException::withMessages(
                    [
                        'contact_ids' => [__('email_marketing_recipients_select_at_least_one')],
                    ]
                );
            }
        } else {
            $data['contact_ids'] = [];
        }

        return $data;
    }
}
