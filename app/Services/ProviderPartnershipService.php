<?php

namespace App\Services;

use App\Enums\ProviderPartnershipStatus;
use App\Mail\ProviderAwaitingCompanySignatureMail;
use App\Mail\ProviderPartnershipCompletedMail;
use App\Mail\ProviderPartnershipInviteMail;
use App\Models\Company;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ProviderPartnershipService
{
    /**
     * Custom or fallback agreement body only (no party / commission header). Not interpolated.
     */
    public function resolvedTermsText(Company $company): string
    {
        $raw = trim((string) ($company->provider_partnership_terms ?? ''));

        if ($raw !== '') {
            return $raw;
        }

        return (string) __('provider_partnership_default_terms', ['company' => $company->name]);
    }

    /**
     * Whether the saved custom terms look like HTML (e.g. from the rich editor).
     */
    public function termsBodyIsHtml(Company $company): bool
    {
        $raw = trim((string) ($company->provider_partnership_terms ?? ''));
        if ($raw === '') {
            return false;
        }

        return (bool) preg_match('/<\s*[a-z][\s\S]*>/i', $raw);
    }

    /**
     * Placeholders for the partnership template editor: {{company_name}}, {{provider_name}}, …
     *
     * @return array<string, string>
     */
    /**
     * Keys for {{…}} tokens in the workspace partnership template editor (labels for UI).
     *
     * @return list<array{key: string, label: string}>
     */
    public function partnershipTemplateEditorHints(): array
    {
        return [
            ['key' => 'company_name', 'label' => __('Template placeholder company name')],
            ['key' => 'provider_name', 'label' => __('Template placeholder provider name')],
            ['key' => 'provider_email', 'label' => __('Template placeholder provider email')],
            ['key' => 'provider_phone', 'label' => __('Template placeholder provider phone')],
            ['key' => 'provider_job_title', 'label' => __('Template placeholder provider job title')],
            ['key' => 'provider_website', 'label' => __('Template placeholder provider website')],
            ['key' => 'commission_percent', 'label' => __('Template placeholder commission percent')],
            ['key' => 'commission_line', 'label' => __('Template placeholder commission line')],
        ];
    }

    public function partnershipTemplatePlaceholders(Provider $provider): array
    {
        $provider->loadMissing('company');
        $company = $provider->company ?? new Company(['name' => '']);

        $pct = '—';
        $line = $this->commissionLineForContract($provider);
        if ($provider->commission_rate !== null && (string) $provider->commission_rate !== '') {
            $pct = number_format((float) $provider->commission_rate * 100, 2);
        }

        return [
            'company_name' => $company->name,
            'provider_name' => $provider->name,
            'provider_email' => (string) ($provider->email ?? ''),
            'provider_phone' => (string) ($provider->phone ?? ''),
            'provider_job_title' => (string) ($provider->job_title ?? ''),
            'provider_website' => (string) ($provider->website ?? ''),
            'commission_percent' => $pct,
            'commission_line' => $line,
        ];
    }

    public function interpolatePartnershipBody(string $body, Provider $provider): string
    {
        $out = $body;
        foreach ($this->partnershipTemplatePlaceholders($provider) as $key => $value) {
            $out = str_replace('{{'.$key.'}}', $value, $out);
        }

        return $out;
    }

    /**
     * Agreement body (custom or default) with {{…}} placeholders replaced for this provider.
     */
    public function resolvedTermsTextForProvider(Provider $provider): string
    {
        $company = $provider->company;
        if (! $company) {
            return '';
        }

        return $this->interpolatePartnershipBody($this->resolvedTermsText($company), $provider);
    }

    /**
     * Plain-text header (parties + commission), no terms block.
     */
    public function contractHeaderPlain(Provider $provider): string
    {
        $provider->loadMissing('company');
        $company = $provider->company;
        if (! $company) {
            return '';
        }

        $blocks = [];
        $blocks[] = __('provider_contract_summary_line', [
            'company' => $company->name,
            'provider' => $provider->name,
        ]);
        $blocks[] = '';
        $blocks[] = '--- '.__('provider_contract_parties_heading').' ---';
        $blocks[] = __('provider_contract_company_line', ['name' => $company->name]);
        $blocks[] = __('provider_contract_provider_line', ['name' => $provider->name]);

        $email = trim((string) ($provider->email ?? ''));
        if ($email !== '') {
            $blocks[] = __('provider_contract_email_line', ['email' => $email]);
        }

        $role = trim((string) ($provider->job_title ?? ''));
        if ($role !== '') {
            $blocks[] = __('provider_contract_role_line', ['role' => $role]);
        }

        $blocks[] = '';
        $blocks[] = '--- '.__('provider_contract_commission_heading').' ---';
        $blocks[] = $this->commissionLineForContract($provider);
        if ($provider->commission_tiers && count((array) $provider->commission_tiers) > 0) {
            $blocks[] = __('provider_contract_commission_tiers_note');
        }

        return implode("\n", $blocks);
    }

    /**
     * Plain document for email (HTML terms stripped).
     */
    public function generatedContractPlainForMail(Provider $provider): string
    {
        $provider->loadMissing('company');
        $company = $provider->company;
        if (! $company) {
            return '';
        }

        $header = $this->contractHeaderPlain($provider);
        $terms = $this->resolvedTermsTextForProvider($provider);
        if ($this->termsBodyIsHtml($company)) {
            $terms = html_entity_decode(strip_tags($terms), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $terms = trim(preg_replace('/\s+/u', ' ', $terms) ?? '');
        }

        return $header."\n\n--- ".__('provider_contract_terms_heading')." ---\n\n".$terms;
    }

    /**
     * Full contract text: parties, commission summary, then terms (custom or default), interpolated.
     */
    public function generatedContractText(Provider $provider): string
    {
        return $this->generatedContractPlainForMail($provider);
    }

    private function commissionLineForContract(Provider $provider): string
    {
        if ($provider->commission_rate !== null && (string) $provider->commission_rate !== '') {
            $pct = number_format((float) $provider->commission_rate * 100, 2);

            return __('provider_contract_commission_percent', ['percent' => $pct]);
        }

        return __('provider_contract_commission_not_set');
    }

    public function sendInviteMail(Provider $provider): void
    {
        $provider->loadMissing(['company', 'user']);
        $user = $provider->user;
        if (! $user || ! $user->email) {
            return;
        }

        Mail::to($user->email)->send(new ProviderPartnershipInviteMail($provider, $this->generatedContractText($provider)));
    }

    public function recordProviderSignature(Provider $provider, ?string $signaturePngDataUrl = null): void
    {
        if ($provider->partnership_status !== ProviderPartnershipStatus::PendingProvider) {
            return;
        }

        $provider->update([
            'partnership_status' => ProviderPartnershipStatus::PendingCompany,
            'partnership_provider_signed_at' => now(),
            'partnership_provider_signature_data' => $signaturePngDataUrl,
        ]);

        $this->notifyCompanyAdminsProviderSigned($provider->fresh(['company', 'user']));
    }

    public function recordCompanySignature(Provider $provider, User $signer): void
    {
        if ($provider->partnership_status !== ProviderPartnershipStatus::PendingCompany) {
            return;
        }

        if (! $signer->hasRole('company_admin')) {
            return;
        }

        if ((string) $signer->company_id !== (string) $provider->company_id) {
            return;
        }

        $provider->update([
            'partnership_status' => ProviderPartnershipStatus::Active,
            'partnership_company_signed_at' => now(),
            'partnership_company_signer_user_id' => $signer->id,
        ]);

        $provider->loadMissing('user');
        if ($provider->user?->email) {
            Mail::to($provider->user->email)->send(new ProviderPartnershipCompletedMail($provider));
        }
    }

    private function notifyCompanyAdminsProviderSigned(Provider $provider): void
    {
        $company = $provider->company;
        if (! $company) {
            return;
        }

        $admins = User::query()
            ->where('company_id', $company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'company_admin'))
            ->get();

        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new ProviderAwaitingCompanySignatureMail($provider));
            }
        }
    }
}
