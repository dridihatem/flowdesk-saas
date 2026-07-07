<?php

namespace App\Support;

/**
 * Built-in platform template catalog used only when running migrations on an empty
 * email_marketing_template_models table. Admins manage the live library in the database.
 */
final class EmailMarketingTemplateModelDefaults
{
    /**
     * @return array<string, array{name: string, category: string, body_html: string}>
     */
    public static function definitions(): array
    {
        $simpleAnnouncement = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;background:#f4f4f5;color:#18181b;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
    <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;">Your headline</h1>
    <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#3f3f46;">Replace this paragraph with your announcement. Keep it short and clear.</p>
    <p style="margin:0;font-size:14px;line-height:1.5;color:#71717a;">— Your company name</p>
  </div>
</body>
</html>
HTML;

        $newsletterDigest = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;background:#eef2ff;color:#1e1b4b;">
  <div style="max-width:600px;margin:0 auto;">
    <div style="background:#4f46e5;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
      <p style="margin:0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.9;">Newsletter</p>
      <h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;">This week at a glance</h1>
    </div>
    <div style="background:#fff;padding:24px;border-radius:0 0 12px 12px;box-shadow:0 4px 6px -1px rgba(0,0,0,.08);">
      <h2 style="margin:0 0 8px;font-size:18px;">Story title one</h2>
      <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">Short summary or teaser. Link to the full article from your campaign editor.</p>
      <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">
      <h2 style="margin:0 0 8px;font-size:18px;">Story title two</h2>
      <p style="margin:0;font-size:15px;line-height:1.6;color:#475569;">Another blurb for your readers.</p>
    </div>
  </div>
</body>
</html>
HTML;

        $promoCta = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;background:#fafafa;color:#171717;">
  <div style="max-width:520px;margin:0 auto;text-align:center;">
    <p style="margin:0 0 12px;font-size:13px;color:#737373;text-transform:uppercase;letter-spacing:.06em;">Limited offer</p>
    <h1 style="margin:0 0 16px;font-size:26px;line-height:1.2;">Headline for your promotion</h1>
    <p style="margin:0 0 24px;font-size:16px;line-height:1.55;color:#404040;">Describe the benefit in one or two sentences. Edit the button label and link when you send.</p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto;">
      <tr>
        <td style="border-radius:8px;background:#171717;">
          <a href="#" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">Call to action</a>
        </td>
      </tr>
    </table>
    <p style="margin:28px 0 0;font-size:12px;color:#a3a3a3;">You can unsubscribe from these emails at any time.</p>
  </div>
</body>
</html>
HTML;

        $introductionFirstContact = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#0f172a;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e2e8f0;">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">Hi [Recipient Name],</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">Thank you for your interest in [Company Name]. I'm [Your Name], and I wanted to introduce myself and our team.</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">We help organizations like yours with [brief value proposition — e.g. clearer workflows, faster delivery, better visibility]. I'd welcome the chance to learn more about your priorities and see if we're a good fit.</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">Would you be open to a short call in the next week? Reply with a time that works for you, or book directly here: [scheduling link].</p>
    <p style="margin:0 0 8px;font-size:15px;line-height:1.6;color:#334155;">Best regards,</p>
    <p style="margin:0;font-size:15px;line-height:1.6;color:#334155;">[Your Name]<br><span style="color:#64748b;font-size:14px;">[Title] · [Company Name]<br>[Phone] · [Email]</span></p>
  </div>
</body>
</html>
HTML;

        $followupAfterMeeting = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#0f172a;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e2e8f0;">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">Hi [Recipient Name],</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">Thank you for taking the time to meet on [Meeting date]. I appreciated our conversation about [topic or goal].</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;"><strong style="color:#0f172a;">Summary</strong><br><span style="color:#475569;">• [Decision or takeaway one]<br>• [Decision or takeaway two]<br>• [Open question or next milestone]</span></p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;"><strong style="color:#0f172a;">Next steps</strong><br><span style="color:#475569;">[Your Name] will: [action + deadline]<br>[Recipient Name] will: [action + deadline]</span></p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;">If anything in this summary needs adjusting, just reply — I'm happy to align before we move forward.</p>
    <p style="margin:0 0 8px;font-size:15px;line-height:1.6;color:#334155;">Thanks again,</p>
    <p style="margin:0;font-size:15px;line-height:1.6;color:#334155;">[Your Name]<br><span style="color:#64748b;font-size:14px;">[Title] · [Company Name]</span></p>
  </div>
</body>
</html>
HTML;

        $generalVersatile = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;background:#fafafa;color:#171717;">
  <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#404040;">Hi [Recipient Name],</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#404040;">[Opening line: reason for writing — thank them, reference a shared context, or state the purpose in one clear sentence.]</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#404040;">[Main message: one or two short paragraphs with the details, request, or update. Keep paragraphs short for mobile readers.]</p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#404040;">[Optional: bullet list]<br><span style="color:#525252;">• [Point one]<br>• [Point two]<br>• [Point three]</span></p>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#404040;">[Closing ask or offer — e.g. "Let me know if you have questions" or "Could you confirm by [date]?"]</p>
    <p style="margin:0 0 8px;font-size:15px;line-height:1.65;color:#404040;">Kind regards,</p>
    <p style="margin:0;font-size:15px;line-height:1.65;color:#404040;">[Your Name]<br><span style="color:#737373;font-size:14px;">[Company Name] · [Email] · [Phone]</span></p>
  </div>
</body>
</html>
HTML;

        $corporateBlueBranded = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{company_name}}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;">
    <tr><td align="center" style="padding:24px 12px;">
      <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr>
          <td style="background:#1e40af;padding:24px;text-align:center;">
            <img src="#" alt="{{company_name}}" width="160" style="display:block;margin:0 auto 12px;max-width:160px;height:auto;border:0;" />
            <p style="margin:0;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#bfdbfe;">{{company_name}}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px 28px;">
            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#111827;">Hi {{first_name}},</p>
            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#111827;">Your headline here</h1>
            <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#374151;">Replace this paragraph with your main message. Use clear, concise copy and a single call to action.</p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
              <tr>
                <td style="border-radius:6px;background:#1e40af;">
                  <a href="#" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;">Call to action</a>
                </td>
              </tr>
            </table>
            <p style="margin:0;font-size:15px;line-height:1.65;color:#374151;">Best regards,<br><strong style="color:#111827;">The {{company_name}} team</strong></p>
          </td>
        </tr>
        <tr>
          <td style="background:#111827;padding:24px 28px;text-align:center;">
            <p style="margin:0 0 8px;font-size:14px;font-weight:bold;color:#ffffff;">{{company_name}}</p>
            <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#9ca3af;">123 Business Street · City · contact@example.com · +1 234 567 890</p>
            <p style="margin:0;font-size:13px;line-height:1.5;">
              <a href="#" style="color:#60a5fa;text-decoration:none;margin:0 8px;">LinkedIn</a>
              <a href="#" style="color:#60a5fa;text-decoration:none;margin:0 8px;">Facebook</a>
              <a href="#" style="color:#60a5fa;text-decoration:none;margin:0 8px;">Instagram</a>
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        return [
            'simple_announcement' => [
                'name' => 'Simple announcement',
                'category' => 'General',
                'body_html' => $simpleAnnouncement,
            ],
            'newsletter_digest' => [
                'name' => 'Newsletter digest',
                'category' => 'Newsletter',
                'body_html' => $newsletterDigest,
            ],
            'promo_cta' => [
                'name' => 'Promo with button',
                'category' => 'Promotional',
                'body_html' => $promoCta,
            ],
            'introduction_first_contact' => [
                'name' => 'Introduction — first contact',
                'category' => 'Sales',
                'body_html' => $introductionFirstContact,
            ],
            'followup_after_meeting' => [
                'name' => 'Follow-up after meeting',
                'category' => 'Sales',
                'body_html' => $followupAfterMeeting,
            ],
            'general_message' => [
                'name' => 'General message (any use)',
                'category' => 'General',
                'body_html' => $generalVersatile,
            ],
            'corporate_blue_branded' => [
                'name' => 'Corporate — blue header & logo',
                'category' => 'Branded',
                'body_html' => $corporateBlueBranded,
            ],
        ];
    }
}
