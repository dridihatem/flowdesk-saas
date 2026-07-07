<?php

namespace App\Support;

/**
 * Workspace email HTML starter: tokenized layout + color presets.
 * Placeholders use %%KEY%% to avoid clashing with {{ merge_tags }}.
 */
final class EmailMarketingStarterLayout
{
    /**
     * @deprecated Use buildFromTokens with defaults; kept for callers expecting one static HTML.
     */
    public static function html(): string
    {
        $first = self::presets()[0] ?? null;

        return self::buildFromTokens($first['tokens'] ?? self::defaultTokens());
    }

    /**
     * Base template with %%TOKENS%% (merge tags like {{name}} stay intact).
     */
    public static function baseTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Email</title>
</head>
<body style="margin:0;padding:0;width:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:%%OUTER_BG%%;">
  <div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;opacity:0;">&#8204;</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:%%OUTER_BG%%;border:0;border-collapse:collapse;">
    <tr>
      <td align="center" style="padding:32px 16px 48px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background-color:%%CARD_BG%%;border-radius:%%CARD_R%%;overflow:hidden;border:%%CARD_BORDER_W%% solid %%CARD_BORDER%%;border-collapse:separate;box-shadow:%%SHADOW%%;">
          <tr>
            <td style="height:4px;background:linear-gradient(90deg,%%BAR%%,%%BAR2%%);background-color:%%BAR%%;line-height:4px;font-size:0;">&nbsp;</td>
          </tr>
          <tr>
            <td align="center" style="padding:40px 40px 16px;">
              <a href="https://example.com" target="_blank" rel="noopener noreferrer" style="text-decoration:none;display:inline-block;">
                <img src="https://placehold.co/200x56/%%LOGO1%%/%%LOGO2%%?text=LOGO" width="200" height="56" alt="{{company_name}}" style="display:block;border:0;outline:none;text-decoration:none;height:auto;max-width:200px;width:200px;" />
              </a>
              <p style="margin:12px 0 0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:%%MUTED%%;">{{company_name}}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 40px 8px;" align="center">
              <h1 style="margin:0;padding:0;font-family:Georgia,serif;font-size:28px;font-weight:700;line-height:1.25;color:%%H1%%;">A note for you</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 40px 8px;" align="left">
              <p style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:16px;line-height:1.6;color:%%P%%;">Hi {{first_name}},</p>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 40px 8px;" align="left">
              <p style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:16px;line-height:1.6;color:%%P%%;">Thank you for being part of our community. This is a great place to share your key message, an announcement, or a special offer.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 40px 8px;" align="left">
              <p style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:16px;line-height:1.6;color:%%P%%;">Replace the sample logo URL with your own, then set your button link and footer text below.</p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:28px 40px 32px;">
              <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                <tr>
                  <td align="center" bgcolor="%%CTA%%" style="border-radius:10px;border:1px solid %%CTA_BORDER%%;">
                    <a href="https://example.com" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 32px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:16px;font-weight:600;text-decoration:none;color:%%CTA_TEXT%%;">View details</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="border-top:1px solid %%RULE%%;padding:24px 40px 32px;" align="center">
              <p style="margin:0 0 8px;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:13px;line-height:1.5;color:%%MUTED%%;">You’re receiving this because you’re on a list for <strong style="color:%%STRONG%%;">{{audience_name}}</strong> at <strong style="color:%%STRONG%%;">{{company_name}}</strong>.</p>
              <p style="margin:0 0 16px;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;line-height:1.5;color:%%FOOTNOTE%%;">Add your business address and legal lines here. Sent to: {{email}}</p>
              <p style="margin:0;padding:0;">
                <a href="#" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;font-weight:600;color:%%LINK%%;text-decoration:underline;">Unsubscribe</a>
                <span style="color:%%RULE%%;">&nbsp;·&nbsp;</span>
                <a href="https://example.com" target="_blank" rel="noopener noreferrer" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:12px;font-weight:600;color:%%LINK%%;text-decoration:underline;">Visit website</a>
              </p>
            </td>
          </tr>
        </table>
        <p style="margin:20px 0 0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:11px;color:%%FOOTNOTE%%;">&nbsp;</p>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultTokens(): array
    {
        $p = self::presets()[0] ?? null;

        return $p['tokens'] ?? [];
    }

    /**
     * @param  array<string, string>  $tokens
     */
    public static function buildFromTokens(array $tokens): string
    {
        $tpl = self::baseTemplate();
        $defaults = self::defaultTokens();
        $merged = array_merge($defaults, $tokens);

        return strtr($tpl, $merged);
    }

    /**
     * Presets for the UI (id, i18n key for name, swatches, full token map).
     *
     * @return list<array{id: string, name_key: string, swatch: list<string>, tokens: array<string, string>}>
     */
    public static function presets(): array
    {
        return [
            self::makePreset(
                'indigo',
                'email_marketing_starter_preset_indigo',
                ['#4f46e5', '#7c3aed', '#f8fafc'],
                [
                    '%%OUTER_BG%%' => '#f1f5f9',
                    '%%CARD_BG%%' => '#ffffff',
                    '%%CARD_BORDER%%' => '#e2e8f0',
                    '%%CARD_BORDER_W%%' => '1px',
                    '%%CARD_R%%' => '16px',
                    '%%SHADOW%%' => '0 10px 40px rgba(15,23,42,0.08)',
                    '%%BAR%%' => '#4f46e5',
                    '%%BAR2%%' => '#7c3aed',
                    '%%LOGO1%%' => '4f46e5',
                    '%%LOGO2%%' => 'ffffff',
                    '%%H1%%' => '#0f172a',
                    '%%P%%' => '#334155',
                    '%%MUTED%%' => '#64748b',
                    '%%STRONG%%' => '#475569',
                    '%%FOOTNOTE%%' => '#94a3b8',
                    '%%RULE%%' => '#e2e8f0',
                    '%%CTA%%' => '#4f46e5',
                    '%%CTA_BORDER%%' => '#4f46e5',
                    '%%CTA_TEXT%%' => '#ffffff',
                    '%%LINK%%' => '#4f46e5',
                ]
            ),
            self::makePreset(
                'ocean',
                'email_marketing_starter_preset_ocean',
                ['#0ea5e9', '#06b6d4', '#f0f9ff'],
                [
                    '%%OUTER_BG%%' => '#e0f2fe',
                    '%%CARD_BG%%' => '#ffffff',
                    '%%CARD_BORDER%%' => '#bae6fd',
                    '%%CARD_BORDER_W%%' => '1px',
                    '%%CARD_R%%' => '16px',
                    '%%SHADOW%%' => '0 12px 32px rgba(14,165,233,0.12)',
                    '%%BAR%%' => '#0284c7',
                    '%%BAR2%%' => '#22d3ee',
                    '%%LOGO1%%' => '0284c7',
                    '%%LOGO2%%' => 'ffffff',
                    '%%H1%%' => '#0c4a6e',
                    '%%P%%' => '#0f172a',
                    '%%MUTED%%' => '#0369a1',
                    '%%STRONG%%' => '#0e7490',
                    '%%FOOTNOTE%%' => '#64748b',
                    '%%RULE%%' => '#bae6fd',
                    '%%CTA%%' => '#0284c7',
                    '%%CTA_BORDER%%' => '#0284c7',
                    '%%CTA_TEXT%%' => '#ffffff',
                    '%%LINK%%' => '#0284c7',
                ]
            ),
            self::makePreset(
                'sunset',
                'email_marketing_starter_preset_sunset',
                ['#f43f5e', '#f97316', '#fff1f2'],
                [
                    '%%OUTER_BG%%' => '#fff7ed',
                    '%%CARD_BG%%' => '#ffffff',
                    '%%CARD_BORDER%%' => '#fed7aa',
                    '%%CARD_BORDER_W%%' => '1px',
                    '%%CARD_R%%' => '20px',
                    '%%SHADOW%%' => '0 10px 36px rgba(244,63,94,0.1)',
                    '%%BAR%%' => '#e11d48',
                    '%%BAR2%%' => '#f97316',
                    '%%LOGO1%%' => 'e11d48',
                    '%%LOGO2%%' => 'ffffff',
                    '%%H1%%' => '#7f1d1d',
                    '%%P%%' => '#431407',
                    '%%MUTED%%' => '#9a3412',
                    '%%STRONG%%' => '#7c2d12',
                    '%%FOOTNOTE%%' => '#a8a29e',
                    '%%RULE%%' => '#fecaca',
                    '%%CTA%%' => '#e11d48',
                    '%%CTA_BORDER%%' => '#e11d48',
                    '%%CTA_TEXT%%' => '#ffffff',
                    '%%LINK%%' => '#e11d48',
                ]
            ),
            self::makePreset(
                'forest',
                'email_marketing_starter_preset_forest',
                ['#059669', '#10b981', '#ecfdf5'],
                [
                    '%%OUTER_BG%%' => '#d1fae5',
                    '%%CARD_BG%%' => '#ffffff',
                    '%%CARD_BORDER%%' => '#a7f3d0',
                    '%%CARD_BORDER_W%%' => '1px',
                    '%%CARD_R%%' => '12px',
                    '%%SHADOW%%' => '0 8px 28px rgba(5,150,105,0.1)',
                    '%%BAR%%' => '#047857',
                    '%%BAR2%%' => '#34d399',
                    '%%LOGO1%%' => '047857',
                    '%%LOGO2%%' => 'ffffff',
                    '%%H1%%' => '#064e3b',
                    '%%P%%' => '#14532d',
                    '%%MUTED%%' => '#047857',
                    '%%STRONG%%' => '#065f46',
                    '%%FOOTNOTE%%' => '#6b7280',
                    '%%RULE%%' => '#a7f3d0',
                    '%%CTA%%' => '#047857',
                    '%%CTA_BORDER%%' => '#047857',
                    '%%CTA_TEXT%%' => '#ffffff',
                    '%%LINK%%' => '#047857',
                ]
            ),
            self::makePreset(
                'noir',
                'email_marketing_starter_preset_noir',
                ['#0f172a', '#334155', '#1e293b'],
                [
                    '%%OUTER_BG%%' => '#0f172a',
                    '%%CARD_BG%%' => '#1e293b',
                    '%%CARD_BORDER%%' => '#334155',
                    '%%CARD_BORDER_W%%' => '1px',
                    '%%CARD_R%%' => '16px',
                    '%%SHADOW%%' => '0 16px 48px rgba(0,0,0,0.4)',
                    '%%BAR%%' => '#6366f1',
                    '%%BAR2%%' => '#8b5cf6',
                    '%%LOGO1%%' => '6366f1',
                    '%%LOGO2%%' => 'f8fafc',
                    '%%H1%%' => '#f1f5f9',
                    '%%P%%' => '#e2e8f0',
                    '%%MUTED%%' => '#94a3b8',
                    '%%STRONG%%' => '#cbd5e1',
                    '%%FOOTNOTE%%' => '#64748b',
                    '%%RULE%%' => '#334155',
                    '%%CTA%%' => '#6366f1',
                    '%%CTA_BORDER%%' => '#818cf8',
                    '%%CTA_TEXT%%' => '#ffffff',
                    '%%LINK%%' => '#a5b4fc',
                ]
            ),
            self::makePreset(
                'paper',
                'email_marketing_starter_preset_paper',
                ['#d4d4d8', '#ffffff', '#fafafa'],
                [
                    '%%OUTER_BG%%' => '#fafafa',
                    '%%CARD_BG%%' => '#ffffff',
                    '%%CARD_BORDER%%' => '#d4d4d8',
                    '%%CARD_BORDER_W%%' => '1px',
                    '%%CARD_R%%' => '4px',
                    '%%SHADOW%%' => '0 1px 3px rgba(0,0,0,0.06)',
                    '%%BAR%%' => '#a1a1aa',
                    '%%BAR2%%' => '#d4d4d8',
                    '%%LOGO1%%' => '52525b',
                    '%%LOGO2%%' => 'ffffff',
                    '%%H1%%' => '#18181b',
                    '%%P%%' => '#3f3f46',
                    '%%MUTED%%' => '#71717a',
                    '%%STRONG%%' => '#52525b',
                    '%%FOOTNOTE%%' => '#a1a1aa',
                    '%%RULE%%' => '#e4e4e7',
                    '%%CTA%%' => '#18181b',
                    '%%CTA_BORDER%%' => '#18181b',
                    '%%CTA_TEXT%%' => '#ffffff',
                    '%%LINK%%' => '#18181b',
                ]
            ),
        ];
    }

    /**
     * @param  array<string, string>  $tokens
     * @return array{id: string, name_key: string, swatch: list<string>, tokens: array<string, string>}
     */
    private static function makePreset(string $id, string $nameKey, array $swatch, array $tokens): array
    {
        return [
            'id' => $id,
            'name_key' => $nameKey,
            'swatch' => $swatch,
            'tokens' => $tokens,
        ];
    }
}
