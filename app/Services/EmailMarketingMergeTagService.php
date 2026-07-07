<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailMarketingAudienceContact;

class EmailMarketingMergeTagService
{
    /**
     * Replace {{name}}, {{email}}, {{company_name}}, etc. in subject or HTML.
     * Unknown tags are left unchanged.
     */
    public function apply(
        string $text,
        Company $company,
        ?EmailMarketingAudienceContact $contact = null,
        ?string $audienceName = null,
    ): string {
        if ($text === '') {
            return $text;
        }

        $map = $this->buildMap($company, $contact, $audienceName);

        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/u',
            function (array $m) use ($map): string {
                $k = mb_strtolower($m[1]);

                return $map[$k] ?? $m[0];
            },
            $text
        );
    }

    /**
     * @return array<string, string> Lowercase key => replacement
     */
    public function buildMap(Company $company, ?EmailMarketingAudienceContact $contact, ?string $audienceName): array
    {
        $name = $contact ? trim((string) ($contact->name ?? '')) : '';
        $email = $contact ? trim((string) ($contact->email ?? '')) : '';
        if ($name === '' && $email !== '') {
            $name = __('email_marketing_merge_default_name');
        } elseif ($name === '') {
            $name = __('email_marketing_merge_default_name');
        }
        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = (string) ($parts[0] ?? '');
        $last = \count($parts) > 1
            ? implode(' ', \array_slice($parts, 1))
            : '';

        $companyName = (string) ($company->name ?? '');
        $logoUrl = $this->companyLogoUrl($company);

        return [
            'name' => $name,
            'full_name' => $name,
            'first_name' => $first,
            'firstname' => $first,
            'last_name' => $last,
            'lastname' => $last,
            'email' => $email,
            'company_name' => $companyName,
            'workspace_name' => $companyName,
            'audience' => (string) ($audienceName ?? ''),
            'audience_name' => (string) ($audienceName ?? ''),
            'company_logo' => $logoUrl,
            'logo' => $logoUrl,
            'workspace_logo' => $logoUrl,
        ];
    }

    private function companyLogoUrl(Company $company): string
    {
        return (string) (app(CompanyThemeService::class)->themeFor($company)['logo_url'] ?? '');
    }
}
