<?php

namespace App\Services;

final class AiAssistantPrompts
{
    public static function system(): string
    {
        return 'You are a concise business writing assistant for company staff using Flowqil '
            .'(projects, invoices, proposals, forms, support tickets, and client communication). '
            .'Reply in clear plain language. No preamble like "Here is". Keep under 500 words unless the user context clearly needs more. '
            .self::outputLanguageInstruction();
    }

    public static function systemForMode(string $mode): string
    {
        if ($mode === 'landing_page') {
            return 'You are an expert landing page designer and front-end developer. '
                .'Output only one complete HTML document. No markdown fences, no commentary. '
                .'Use embedded CSS, responsive layout, accessible contrast, and professional marketing copy. '
                .self::outputLanguageInstruction();
        }

        return self::system();
    }

    public static function maxTokensForMode(string $mode): int
    {
        return match ($mode) {
            'landing_page' => 8192,
            'project_description' => 4096,
            default => 2048,
        };
    }

    /**
     * Human-readable language name for prompts (uses intl when available).
     */
    public static function labelForLocale(string $locale): string
    {
        $locale = trim($locale) !== '' ? trim($locale) : 'en';
        if (extension_loaded('intl') && class_exists(\Locale::class, false)) {
            $name = \Locale::getDisplayLanguage($locale, 'en');
            if ($name !== '') {
                return $name;
            }
        }

        return strtoupper($locale);
    }

    /**
     * Instructs the model to answer in the workspace UI language (session / user / company default).
     */
    public static function outputLanguageInstruction(?string $locale = null): string
    {
        $locale = $locale !== null && $locale !== '' ? $locale : app()->getLocale();
        $name = self::labelForLocale($locale);

        return "Output language: write the entire reply in {$name} (locale {$locale}). "
            .'If the user’s context is clearly in one other language only, you may use that language for this reply instead; '
            .'do not mix languages in the same reply unless the context itself mixes them.';
    }

    /**
     * For JSON workflow (project description + tasks): single block appended to the system prompt.
     */
    public static function workflowJsonOutputLanguageRules(): string
    {
        $locale = app()->getLocale();
        $name = self::labelForLocale($locale);

        return 'Output language (mandatory): the workspace default is '.$name." (BCP-47: {$locale}). "
            .'Write "description_html" and every task "title" and "description" in '.$name.'. '
            .'If the project text below is clearly in a single other language only, use that language for all fields instead.';
    }

    public static function user(string $mode, string $context): string
    {
        $ctx = trim($context);
        $ctxLine = $ctx !== '' ? "Context from the user:\n{$ctx}" : 'No extra context was provided.';

        return match ($mode) {
            'proposal' => "Task: Draft a client-ready proposal outline for a quote.\n"
                ."Use markdown with these sections exactly:\n"
                ."## Scope\n## Deliverables\n## Timeline\n## Pricing approach\n## Suggested line items (3–8 bullets with short labels suitable for a quote)\n"
                ."Keep it practical and under 500 words unless the context needs more.\n{$ctxLine}",
            'pricing' => "Task: Suggest a pricing approach (options, tiers, upsell path) appropriate for the service described.\n{$ctxLine}",
            'form' => "Task: Suggest form sections and fields (aim for high conversion; at most 8 fields) for collecting client requirements.\n{$ctxLine}",
            'ticket' => "Task: From the notes, produce (1) a clear one-line subject line for an internal support ticket, then (2) a structured message body with: problem summary, steps tried, expected vs actual, urgency. Use markdown headings.\n{$ctxLine}",
            'client_email' => "Task: Turn the rough notes into a short, professional email to a client or partner. Include subject line on its own first line prefixed \"Subject:\", then the body. Tone: polite and confident.\n{$ctxLine}",
            'task_followup' => "Task: Produce a concise project status update: bullet list of done / in progress / blocked, plus one suggested next step. Suitable for Slack or email.\n{$ctxLine}",
            'seo' => "Task: SEO recommendations for the business described below.\n"
                ."You cannot crawl the site or see Google Search Console data. Do not claim current rankings or search volume.\n"
                .'Output structured markdown with these sections: (1) On-page: title tag, meta description, H1, internal links, URL slug tips. '
                .'(2) Content: topics, FAQ ideas, E-E-A-T hints. (3) Technical: Core Web Vitals, mobile, schema.org types to consider. '
                ."(4) Local / international: hreflang or local landing pages if relevant to the market they mention.\n"
                ."Keep it practical and prioritized (quick wins first). Under 600 words unless the context is very long.\n{$ctxLine}",
            'project_description' => "Task: Write an internal project description / scope for delivery teams.\n"
                ."Output HTML only (no markdown fences). Allowed tags: <p>, <ul>, <ol>, <li>, <strong>, <em>, <h3>, <br>. No scripts, styles, or inline event handlers.\n"
                ."Structure: short overview, scope and deliverables as bullets, assumptions or constraints if relevant, timeline or milestones if the user mentions them.\n"
                ."Follow the output language in the system instructions; if the user’s instructions in the context are clearly in one other language, you may use that language for the HTML only.\n"
                ."Under 800 words unless the user asks for more detail.\n{$ctxLine}",
            'growth_projects' => "Task: Act as a growth advisor for project operations. Review the workspace data and produce actionable decisions.\n"
                .'Output markdown with sections: (1) **Priority decisions** — 3–5 numbered decisions the manager should make this week. '
                .'(2) **Stalled or at-risk projects** — which to chase and why. (3) **Capacity & pipeline** — what to start, pause, or finish. '
                ."(4) **Next steps** — concrete actions with owners (sales, delivery, finance). Be specific; reference client or project names from the context when available.\n{$ctxLine}",
            'growth_invoices' => "Task: Act as a cash-collection and revenue growth advisor. Review invoice data and recommend decisions.\n"
                .'Output markdown with sections: (1) **Collection priorities** — which invoices to chase first (amount, age, client). '
                .'(2) **Reminder strategy** — tone and timing suggestions. (3) **Revenue risks** — patterns that could hurt growth. '
                ."(4) **Decisions** — 3–5 clear yes/no or do-now recommendations for the finance lead.\n{$ctxLine}",
            'growth_clients' => "Task: Act as a client growth and retention advisor. Review client portfolio data and suggest how to grow the company.\n"
                .'Output markdown with sections: (1) **Retain** — clients to protect and how. (2) **Upsell / expand** — best expansion opportunities. '
                ."(3) **Re-engage** — dormant accounts worth reviving. (4) **Decisions** — 3–5 prioritized actions with expected impact (high/medium/low).\n{$ctxLine}",
            'report_counsel' => "Task: Act as a senior business counsel reviewing a workspace report. Analyze the metrics and produce executive guidance to grow the company.\n"
                .'Output markdown with sections: (1) **Executive summary** — 3–5 bullet insights. (2) **Projects** — delivery and pipeline decisions. '
                .'(3) **Invoices & cash** — collection and revenue actions. (4) **Clients & growth** — retention and expansion moves. '
                ."(5) **Priority decisions** — numbered list of concrete decisions for this week (who should act: sales, delivery, finance). Be specific and practical.\n{$ctxLine}",
            'landing_page' => "Task: Generate a complete, self-contained landing page as HTML.\n"
                ."Output ONE full HTML document only (start with <!DOCTYPE html>). No markdown fences, no explanation before or after.\n"
                ."Include embedded <style> in <head> with modern, mobile-responsive CSS (flex/grid, readable typography, clear CTA buttons).\n"
                ."Structure: hero with headline + subhead + primary CTA, features/benefits section, social proof or stats, optional comparison table, FAQ or details, footer with contact.\n"
                ."Use semantic tags: header, main, section, footer, h1-h3, p, ul, table, a, img.\n"
                ."Images: use https://placehold.co/WIDTHxHEIGHT/hexbg/hextext?text=Label with descriptive alt text (no broken relative paths).\n"
                ."Links: use #cta, #features, mailto: or https://example.com placeholders where appropriate.\n"
                ."Brand colors: infer from the business described in the context; default to professional indigo/slate if unknown.\n"
                ."No <script>, no external JS, no inline event handlers (onclick). No forms that POST externally — use CTA links only.\n"
                ."Match the output language from system instructions for all visible text.\n{$ctxLine}",
            default => "Task: Write a brief executive summary (goals, risks, next steps) based on the notes.\n{$ctxLine}",
        };
    }
}
