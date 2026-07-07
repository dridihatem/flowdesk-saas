<?php

namespace App\Services;

use App\Mail\EmailMarketingCampaignMail;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\EmailMarketingAudienceContact;
use App\Models\EmailMarketingCampaign;
use App\Models\EmailMarketingRecipientDelivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailMarketingCampaignSendService
{
    public function __construct(
        private MarketingIntegrationConfigService $integrationConfig,
        private SendGridMailService $sendGridMail,
        private EmailMarketingPixelService $pixel,
        private EmailMarketingMergeTagService $mergeTags,
    ) {}

    /**
     * @throws \RuntimeException
     */
    public function send(EmailMarketingCampaign $campaign): void
    {
        if ($campaign->status === 'sent') {
            throw new \RuntimeException(__('email_marketing_campaign_already_sent'));
        }

        if ($campaign->audience_id === null) {
            throw new \RuntimeException(__('email_marketing_campaign_needs_audience'));
        }

        $campaign->load(['audience.contacts', 'selectedAudienceContacts']);

        $contacts = $this->resolveContacts($campaign);
        if ($contacts->isEmpty()) {
            throw new \RuntimeException(__('email_marketing_campaign_audience_empty'));
        }

        $subject = trim((string) $campaign->subject);
        $body = trim((string) $campaign->body_html);
        if ($subject === '' || $body === '') {
            throw new \RuntimeException(__('email_marketing_campaign_needs_subject_body'));
        }

        $company = Company::query()->withoutGlobalScopes()->findOrFail($campaign->company_id);
        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $smtp = $settings?->smtp;

        $integration = $this->integrationConfig->getResolved($company);
        $mode = $integration['campaign_email'];
        $sendgridKey = $integration['sendgrid']['api_key'] ?? null;

        [$fromAddress, $fromName] = $this->resolveFrom($settings, $smtp);

        $campaign->loadMissing('audience');

        if ($mode === MarketingIntegrationConfigService::CAMPAIGN_EMAIL_SENDGRID && is_string($sendgridKey) && $sendgridKey !== '') {
            try {
                $this->sendMassWithSendGrid($sendgridKey, $company, $campaign, $contacts, $subject, $body, $fromAddress, $fromName);
            } catch (\Throwable $e) {
                Log::error('email_marketing_sendgrid_batch', ['message' => $e->getMessage()]);
                if ($e instanceof \RuntimeException) {
                    throw $e;
                }
                throw new \RuntimeException($e->getMessage(), 0, $e);
            }
            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return;
        }

        if ($mode === MarketingIntegrationConfigService::CAMPAIGN_EMAIL_SENDGRID && ($sendgridKey === null || $sendgridKey === '')) {
            throw new \RuntimeException(__('email_marketing_sendgrid_key_missing'));
        }

        if ($mode === MarketingIntegrationConfigService::CAMPAIGN_EMAIL_TENANT_SMTP) {
            if (! is_array($smtp) || empty($smtp['host'])) {
                throw new \RuntimeException(__('email_marketing_tenant_smtp_required'));
            }
        }

        $useTenant = is_array($smtp) && ! empty($smtp['host']);
        $mailerName = 'flowdesk_tenant';

        if ($useTenant) {
            $enc = $smtp['encryption'] ?? 'tls';
            if ($enc === 'null' || $enc === '') {
                $enc = null;
            }

            Config::set('mail.mailers.'.$mailerName, [
                'transport' => 'smtp',
                'host' => $smtp['host'],
                'port' => (int) ($smtp['port'] ?? 587),
                'encryption' => $enc,
                'username' => $smtp['username'] ?? null,
                'password' => $smtp['password'] ?? null,
                'timeout' => null,
            ]);
        }

        $this->sendMassWithSmtp($company, $campaign, $contacts, $subject, $body, $fromAddress, $fromName, $useTenant, $mailerName);

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Send a one-off preview to an address (does not mark the campaign as sent).
     *
     * @throws \RuntimeException
     */
    public function sendSample(EmailMarketingCampaign $campaign, string $toEmail): void
    {
        $company = Company::query()->withoutGlobalScopes()->findOrFail($campaign->company_id);

        $this->sendPreviewEmail(
            $company,
            $toEmail,
            trim((string) $campaign->subject),
            trim((string) $campaign->body_html),
            $campaign,
        );
    }

    /**
     * Send a preview message with the given subject/body (e.g. from the editor before save).
     *
     * @throws \RuntimeException
     */
    public function sendPreviewEmail(
        Company $company,
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?EmailMarketingCampaign $campaign = null,
    ): void {
        $toEmail = filter_var(trim($toEmail), FILTER_VALIDATE_EMAIL);
        if (! is_string($toEmail) || $toEmail === '') {
            throw new \RuntimeException(__('email_marketing_sample_invalid_email'));
        }

        $subject = trim($subject);
        $body = trim($bodyHtml);
        if ($body === '') {
            throw new \RuntimeException(__('email_marketing_campaign_needs_subject_body'));
        }
        if ($subject === '') {
            $subject = __('email_marketing_preview_default_subject');
        }

        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $smtp = $settings?->smtp;

        if ($campaign !== null) {
            $campaign->loadMissing('audience');
        }

        $integration = $this->integrationConfig->getResolved($company);
        $mode = $integration['campaign_email'];
        $sendgridKey = $integration['sendgrid']['api_key'] ?? null;

        [$fromAddress, $fromName] = $this->resolveFrom($settings, $smtp);

        $sampleContact = new EmailMarketingAudienceContact([
            'name' => __('email_marketing_sample_merge_name'),
            'email' => $toEmail,
        ]);
        $audienceName = $campaign?->audience?->name;
        $subjectMerged = $this->mergeTags->apply($subject, $company, $sampleContact, $audienceName);
        $bodyMerged = $this->mergeTags->apply($body, $company, $sampleContact, $audienceName);

        $token = $this->pixel->newTrackingToken();
        if ($campaign !== null) {
            EmailMarketingRecipientDelivery::query()->create([
                'company_id' => $campaign->company_id,
                'email_marketing_campaign_id' => $campaign->id,
                'email_marketing_audience_contact_id' => null,
                'recipient_email' => $toEmail,
                'kind' => EmailMarketingRecipientDelivery::KIND_SAMPLE,
                'tracking_token' => $token,
                'sent_at' => now(),
            ]);
        }
        $html = $this->pixel->appendTrackingPixel($bodyMerged, $token);
        $sendSubject = __('email_marketing_sample_subject_prefix').$subjectMerged;

        if ($mode === MarketingIntegrationConfigService::CAMPAIGN_EMAIL_SENDGRID && is_string($sendgridKey) && $sendgridKey !== '') {
            $this->sendGridMail->sendHtmlPerRecipient(
                $sendgridKey,
                $sendSubject,
                [[
                    'email' => $toEmail,
                    'name' => (string) $sampleContact->name,
                    'html' => $html,
                    'subject' => $sendSubject,
                ]],
                $fromAddress,
                $fromName
            );

            return;
        }

        if ($mode === MarketingIntegrationConfigService::CAMPAIGN_EMAIL_SENDGRID) {
            throw new \RuntimeException(__('email_marketing_sendgrid_key_missing'));
        }

        if ($mode === MarketingIntegrationConfigService::CAMPAIGN_EMAIL_TENANT_SMTP) {
            if (! is_array($smtp) || empty($smtp['host'])) {
                throw new \RuntimeException(__('email_marketing_tenant_smtp_required'));
            }
        }

        $useTenant = is_array($smtp) && ! empty($smtp['host']);
        $mailerName = 'flowdesk_tenant';

        if ($useTenant) {
            $enc = $smtp['encryption'] ?? 'tls';
            if ($enc === 'null' || $enc === '') {
                $enc = null;
            }
            Config::set('mail.mailers.'.$mailerName, [
                'transport' => 'smtp',
                'host' => $smtp['host'],
                'port' => (int) ($smtp['port'] ?? 587),
                'encryption' => $enc,
                'username' => $smtp['username'] ?? null,
                'password' => $smtp['password'] ?? null,
                'timeout' => null,
            ]);
        }

        $mailable = new EmailMarketingCampaignMail($sendSubject, $html, (string) $fromAddress, (string) $fromName);
        if ($useTenant) {
            Mail::mailer($mailerName)->to($toEmail)->send($mailable);
        } else {
            Mail::to($toEmail)->send($mailable);
        }
    }

    /**
     * @param  Collection<int, EmailMarketingAudienceContact>  $contacts
     */
    private function sendMassWithSendGrid(
        string $sendgridKey,
        Company $company,
        EmailMarketingCampaign $campaign,
        Collection $contacts,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName,
    ): void {
        $audienceName = $campaign->audience?->name;
        $recipients = [];
        foreach ($contacts as $contact) {
            if (! $contact instanceof EmailMarketingAudienceContact) {
                continue;
            }
            $to = filter_var((string) $contact->email, FILTER_VALIDATE_EMAIL);
            if (! is_string($to) || $to === '') {
                continue;
            }
            $token = $this->pixel->newTrackingToken();
            EmailMarketingRecipientDelivery::query()->create([
                'company_id' => $campaign->company_id,
                'email_marketing_campaign_id' => $campaign->id,
                'email_marketing_audience_contact_id' => $contact->id,
                'recipient_email' => $to,
                'kind' => EmailMarketingRecipientDelivery::KIND_MASS,
                'tracking_token' => $token,
                'sent_at' => now(),
            ]);
            $subj = $this->mergeTags->apply($subject, $company, $contact, $audienceName);
            $bodyHtml = $this->mergeTags->apply($body, $company, $contact, $audienceName);
            $recipients[] = [
                'email' => $to,
                'name' => (string) ($contact->name ?? $to),
                'html' => $this->pixel->appendTrackingPixel($bodyHtml, $token),
                'subject' => $subj,
            ];
        }
        if ($recipients === []) {
            throw new \RuntimeException(__('email_marketing_campaign_audience_empty'));
        }
        $this->sendGridMail->sendHtmlPerRecipient(
            $sendgridKey,
            $subject,
            $recipients,
            $fromAddress,
            $fromName
        );
    }

    /**
     * @param  Collection<int, EmailMarketingAudienceContact>  $contacts
     */
    private function sendMassWithSmtp(
        Company $company,
        EmailMarketingCampaign $campaign,
        Collection $contacts,
        string $subject,
        string $body,
        string $fromAddress,
        string $fromName,
        bool $useTenant,
        string $mailerName,
    ): void {
        $audienceName = $campaign->audience?->name;
        foreach ($contacts as $contact) {
            if (! $contact instanceof EmailMarketingAudienceContact) {
                continue;
            }
            $to = filter_var((string) $contact->email, FILTER_VALIDATE_EMAIL);
            if (! is_string($to) || $to === '') {
                continue;
            }
            $token = $this->pixel->newTrackingToken();
            EmailMarketingRecipientDelivery::query()->create([
                'company_id' => $campaign->company_id,
                'email_marketing_campaign_id' => $campaign->id,
                'email_marketing_audience_contact_id' => $contact->id,
                'recipient_email' => $to,
                'kind' => EmailMarketingRecipientDelivery::KIND_MASS,
                'tracking_token' => $token,
                'sent_at' => now(),
            ]);
            $subj = $this->mergeTags->apply($subject, $company, $contact, $audienceName);
            $bodyHtml = $this->mergeTags->apply($body, $company, $contact, $audienceName);
            $html = $this->pixel->appendTrackingPixel($bodyHtml, $token);
            $mailable = new EmailMarketingCampaignMail($subj, $html, (string) $fromAddress, (string) $fromName);
            try {
                if ($useTenant) {
                    Mail::mailer($mailerName)->to($to)->send($mailable);
                } else {
                    Mail::to($to)->send($mailable);
                }
            } catch (\Throwable $e) {
                Log::error('email_marketing_campaign_send_failed', [
                    'campaign_id' => $campaign->id,
                    'email' => $to,
                    'message' => $e->getMessage(),
                ]);
                throw new \RuntimeException(__('email_marketing_campaign_send_failed', [
                    'email' => $to,
                    'message' => $e->getMessage(),
                ]), 0, $e);
            }
        }
    }

    /**
     * @return Collection<int, EmailMarketingAudienceContact>
     */
    private function resolveContacts(EmailMarketingCampaign $campaign): Collection
    {
        if ($campaign->recipient_scope === 'selected') {
            $list = $campaign->selectedAudienceContacts;
            if ($list->isEmpty()) {
                return collect();
            }

            return $list->filter(function (EmailMarketingAudienceContact $c) use ($campaign): bool {
                return (string) $c->audience_id === (string) $campaign->audience_id;
            })->values();
        }

        if ($campaign->audience === null || $campaign->audience->contacts->isEmpty()) {
            return collect();
        }

        return $campaign->audience->contacts;
    }

    /**
     * @param  array<string, mixed>|null  $smtp
     * @return array{0: string, 1: string}
     */
    private function resolveFrom(?CompanySetting $settings, ?array $smtp): array
    {
        $useTenant = is_array($smtp) && ! empty($smtp['host']);
        $fromAddress = $useTenant ? ($smtp['from_address'] ?? config('mail.from.address')) : config('mail.from.address');
        $fromName = $useTenant ? ($smtp['from_name'] ?? config('mail.from.name')) : config('mail.from.name');
        $fromAddress = $fromAddress !== null && $fromAddress !== '' ? $fromAddress : config('mail.from.address');
        $fromName = $fromName !== null && $fromName !== '' ? $fromName : config('mail.from.name');

        return [(string) $fromAddress, (string) $fromName];
    }
}
