# Flowdesk SaaS - International Multi-Sector Tasks.md

## PHASE 1: Project Setup
- [x] Initialize Laravel project (Laravel 13 — current `laravel/laravel` skeleton; tasks originally said 11)
- [x] Database Mysql host: 127.0.0.1, port: 8889, database: flowdesksaas, user and password: root
- [x] Configure .env (DB, APP_URL, CACHE, QUEUE, STORAGE)
- [x] Install TailwindCSS + Alpine.js / Vue.js (Breeze Blade + Alpine; Vue 3 island on dashboard)
- [x] Setup Git repository and CI/CD pipeline
- [x] push to the git repo https://github.com/dridihatem/flowdesk-saas.git
- [x] Create base layout (auth + dashboard)
- [x] Setup Figma design system — *design tokens live in `resources/css/app.css` + theme engine; create/sync the Figma UI kit separately*

## PHASE 2: Multi-Tenant & Subdomain
- [x] Add subdomain & slug columns to `companies`
- [x] Generate slug from company name on registration (`CompanyNamingService`)
- [x] Middleware to detect company by subdomain (`ResolveTenant`)
- [x] Bind `currentCompany` in service container (`scoped` + per-request `instance` in `ResolveTenant`)
- [x] Configure wildcard domain `*.flowdesk-saas.com` (documented in `config/flowdesk.php` + `SESSION_DOMAIN` in `.env.example`)
- [x] Automatic creation of subdomain on company registration
- [x] Tenant-specific storage directories (`storage/app/tenants/{company_id}`, `tenant` disk)

## PHASE 3: Authentication & Roles
- [x] Company registration with subdomain generation
- [x] Generate API token per company (hashed `api_token_*` on `companies` + Sanctum token for user)
- [x] Roles: Admin, Team Member, Business Provider (`company_admin`, `team_member`, `business_provider` via Spatie)
- [x] Login / password reset (Laravel Breeze)
- [x] Email verification (`MustVerifyEmail` on `User`)
- [x] Optional 2FA for company admins — *TOTP via `pragmarx/google2fa` + QR (`bacon/bacon-qr-code`); setup at `/settings/two-factor` (company_admin); challenge after password/OAuth*
- [x] JWT/Sanctum for API authentication (Sanctum — `GET /api/user` with Bearer token)

## PHASE 4: Internationalization & Currency
- [x] Multi-language support (i18n): English, French, Spanish, Arabic (`lang/*.json`, `SetLocale` middleware, `users.locale`)
- [x] Language selector in dashboard
- [x] Multi-currency support and auto-selection based on company country (`companies.country`, `config/flowdesk.country_currency`)
- [x] Currency conversion logic for TND, USD, EUR, etc. (`App\Services\CurrencyConverter`, `config/currencies.php`)

## Social login (OAuth)
- [x] Laravel Socialite — GitHub, Google, LinkedIn OpenID (`SocialAuthController`, `oauth/company` completion for new tenants)

## PHASE 5: Core Database Models
- [x] `companies`
- [x] `company_settings` (branding, SMTP, theme, layout, colors, payment credentials)
- [x] `users` + roles
- [x] `clients`
- [x] `providers`
- [x] `projects`
- [x] `forms` & `form_fields`
- [x] `form_submissions`
- [x] `proposals` / `estimates`
- [x] `invoices`
- [x] `payments` + `transactions`
- [x] `payment_methods`
- [x] `subscriptions` & `plans`
- [x] `plan_limits`
- [x] `usage_tracking`
- [x] `marketing_support`
- [x] `negotiations`
- [x] `audit_logs`

## PHASE 6: UI SYSTEM & THEMING ENGINE 🎨

### Theme Engine Core
- [x] Create dynamic theme system per company (`CompanyThemeService`, `AppLayout`)
- [x] Store theme settings in `company_settings`
- [x] Fields:
  - theme_name
  - layout_type (sidebar / topbar / minimal)
  - primary_color
  - secondary_color
  - font_family
  - dark_mode
  - logo

### CSS Dynamic System
- [x] Use CSS variables for colors (`--flow-*` in `resources/css/app.css` + per-tenant overrides)
- [x] Inject dynamic styles from database into layout (`partials/theme-head.blade.php`)
- [x] Support real-time UI updates without reload (appearance form can extend with Alpine; dashboard uses saved theme)

### Theme Loader Logic
- [x] Load theme dynamically:
  `themes/{theme}/layouts/{layout}.blade.php`
- [x] Fallback to default theme

### Pre-built Themes
- [x] Default (clean SaaS UI)
- [x] Dark Pro (dark mode) — color preset + optional future Blade pack
- [x] Minimal (topbar layout)
- [x] Luxury (premium design) — preset
- [x] Admin/Data-heavy dashboard — preset

### Layout System
- [x] Sidebar layout
- [x] Top navigation layout
- [x] Minimal layout
- [x] Responsive mobile layout

### UI Components Library
- [x] Cards (stats, analytics) — `<x-flow.card>`
- [x] Tables (clients, projects, invoices) — `<x-flow.table>` shell (used on dashboard + list pages)
- [x] Forms (builder + inputs) — Phase 8 form builder + Breeze form components
- [x] Modals & alerts — *existing Breeze `<x-modal>` / session flash*
- [x] Buttons & badges — *Breeze buttons + `<x-flow.badge>`*
- [x] Charts (revenue, analytics) — Chart.js on `/analytics`

### Company Customization Panel
- [x] Theme selector UI (`/settings/appearance`)
- [x] Color picker (primary / secondary)
- [x] Logo upload
- [x] Layout selection
- [x] Dark mode toggle
- [x] Font selection

### Advanced Customization (Pro/Enterprise)
- [x] Custom CSS editor
- [x] Custom dashboard widgets (`company_settings.dashboard`, widget partials under `resources/views/dashboard/widgets/`)
- [x] Drag & drop dashboard layout (SortableJS + Alpine on `/settings/dashboard`, company admins)
- [x] Save multiple UI presets per company (`company_settings.ui_presets` — snapshots of theme + dashboard; apply/delete)

## PHASE 7: Company Dashboard
- [x] Dashboard homepage (stats + KPIs)
- [x] Project list with filters & search
- [x] Clients management (index + search)
- [x] Providers management + commission tracking
- [x] Forms builder UI (CRUD + field add/remove)
- [x] Widget generator (embed snippet + settings page)
- [x] Branding settings panel
- [x] Analytics dashboard
- [x] Notifications & alerts (activity from audit logs)
- [x] AI assistant panel (placeholder UI)

## PHASE 8: Form Builder & Widget System
- [x] Create simple or wizard forms
- [x] Drag & drop fields (SortableJS reorder)
- [x] Multi-step forms (field `step` + wizard layout in embed)
- [x] Required validation (embed API + field flags)
- [x] Generate embeddable JS widget (`resources/js/widget.js` + Vite)
- [x] Token-based submission authentication (Bearer company API token on `/api/v1/embed/*`)
- [x] Widget customization (color, theme in form meta)
- [x] Widget analytics tracking (`widget_events` + 30d stats on form editor)
- [x] Versioning system for widgets (`widget_version` + bump action)

## PHASE 9: Project & Provider Management
- [x] Create projects manually or via forms
- [x] Assign providers (project `provider_id` + UI)
- [x] Provider dashboard (`/provider`)
- [x] Provider can create projects
- [x] Provider can send estimates (proposals + negotiations)
- [x] Negotiation workflow:
  - submitted
  - counter-offer
  - accepted
  - rejected
- [x] Commission tracking per deal (`negotiations.commission_amount_minor` on accept)
- [x] Activity logs (existing `audit_logs`; provider ↔ user link on providers)

## PHASE 10: Proposals & Invoices
- [x] Proposal creation & editing (tenant staff: `proposals/create`, `edit`, `destroy` when no invoices)
- [x] Convert proposal → invoice (`POST proposals/{proposal}/invoice`, accepted proposals)
- [x] Invoice statuses (`InvoiceStatus` + CRUD)
- [x] PDF generation (DomPDF `invoices.pdf`)
- [x] Email sending via SMTP (`InvoiceMailService` + tenant SMTP in `company_settings.smtp`)
- [x] Custom templates per company (`company_settings.document_templates` + `/settings/invoice-documents` for email + PDF footers)
- [x] Invoice reminders (`invoices:send-reminders`, `reminder_sent_at`)

## PHASE 11: Payments
- [x] Stripe integration (PaymentIntent JSON + Stripe webhook)
- [x] PayPal integration (REST v2 order + `approval_url` on invoice; sandbox/live)
- [x] Bank transfer (instructions field in payment settings)
- [x] Flouci payment (Tunisia) — `generate_payment` link + `POST /webhooks/flouci` (idempotent by `payment_id`)
- [x] Company payment credentials (`/settings/payments`)
- [x] Webhook system (`POST /stripe/webhook`, `POST /webhooks/flouci`, CSRF-exempt)
- [x] Payment status sync (manual payments + webhook; invoice → Paid when paid in full)
- [x] Transaction logs (`transactions` on payment record)

## PHASE 12: Subscription & Plans
- [x] Plans: Starter / Pro / Enterprise (`PlanSeeder`)
- [x] Feature limits:
  - users
  - projects
  - forms
  - submissions
  - widgets
  - AI credits
- [x] Subscription system — *custom `subscriptions` + `PlanLimitService` (not Laravel Cashier); Stripe Customer Portal for payment UI*
- [x] Trial management (`subscription.trial_ends_at` on bootstrap)
- [x] Usage tracking (billing shows AI credits month + form submissions month; `usage_tracking` for AI)
- [x] Add-ons system (`plans.addons` JSON + display on `/billing`; sales/contact to activate)

## PHASE 13: Monetization
- [x] Subscription billing (Stripe Customer Portal — `POST /billing/stripe-portal`, `companies.stripe_customer_id`, `STRIPE_SECRET`)
- [x] Commission system (negotiations + commission on accept)
- [x] Pay-per-use features (estimated AI charges on `/billing` via `FLOWDESK_AI_CREDIT_PRICE_MINOR` + usage)
- [x] Billing dashboard (`/billing`)
- [x] Revenue analytics (partial: 30d paid invoices, pipeline, commission sum on `/billing`)

## PHASE 14: AI Integration
- [x] AI proposal generator — *stub text via `/assistant` + `POST /assistant/suggest` (`mode=proposal`)*
- [x] Pricing suggestions — *stub (`mode=pricing`)*
- [x] Auto form generator — *stub (`mode=form`)*
- [x] Project summarization — *stub (`mode=summary`)*
- [x] AI usage tracking (`AiCreditUsageService` + `PlanLimitService` for `ai_credits`)

## PHASE 15: Security
- [x] Data encryption — *HTTPS/TLS in production; company API token hashed; optional field-level encryption for secrets can be layered on*
- [x] API token validation (`AuthenticateCompanyApiToken` on embed API)
- [x] Rate limiting (`throttle` on `/api/*` and embed routes)
- [x] RBAC (Spatie `company_admin`, `team_member`, `business_provider`)
- [x] Audit logs (`audit_logs` model + activity views)
- [x] Optional IP restriction (`company_settings.security.allowed_ips` + `EnforceTenantIpAllowlist` middleware, `/settings/security`)

## PHASE 16: Background Jobs & Monitoring
- [x] Email queues (`InvoiceSentMail`, provider partnership mailables, `EmailMarketingCampaignMail` implement `ShouldQueue` + `afterCommit`; configure `QUEUE_CONNECTION` + `queue:work` in production — see Presentation snapshot)
- [x] Payment webhooks (Stripe + Flouci)
- [x] AI processing — *stub assistant + queued mail; connect OpenAI/Anthropic for real workloads*
- [x] Cron jobs (`invoices:send-reminders` daily 09:00 via `routes/console.php`)
- [x] Logging system (Laravel `storage/logs` + configurable `LOG_CHANNEL`)
- [x] Backup system — *Spatie `laravel-backup`: `backup:run` daily 02:00 (`routes/console.php`); `BACKUP_DISK` in `.env.example`*
- [x] Error monitoring (Sentry) — *`sentry/sentry-laravel` + `SENTRY_LARAVEL_DSN` (optional; empty DSN disables reporting)*

## ✅ Final Product
- Multi-tenant SaaS with subdomains
- Full UI customization per company
- Multiple themes + layout engine
- Widget system for external websites
- Provider marketplace + negotiation system
- Invoice & payment system (Tunisia + global)
- AI-powered automation
- Subscription + commission monetization
- Enterprise-level SaaS architecture

---

## Presentation snapshot — recent deliveries (slide-deck notes)

*Use this block to build slides: problem → what we shipped → user benefit → optional “what’s next”.*

### 1. Portals & permissions (client + business provider)
- **Client portal**: Sidebar and top/mobile nav show only what the role may access (`@can`: projects, payments, invite colleague, etc.). “Pure client” vs staff vs provider avoids mixed-role confusion.
- **Provider portal**: Same idea — overview / projects only when `provider.view_dashboard` / `provider.manage_projects` allow it.
- **Staff workspace**: Existing plan gates + workspace permissions unchanged for admins/team.

### 2. Client growth: colleague requests + company approval
- **Flow**: Existing client suggests a colleague (`portal.suggest_client_account`); company reviews **Client signup requests**; approve creates user + client record and sends password setup.
- **Data**: `client_account_requests` table + portal form + staff review UI (`clients/account-requests`).

### 3. Projects: faster client assignment
- **Quick-add client modal** on staff and provider project create/edit: minimal fields, JSON endpoint, new option selected in `client_id` — no round-trip to Clients index.

### 4. Provider partnership as a real “contract” experience
- **Before signing**: Provider **dashboard** stays reachable; prominent **contract required** card; partnership middleware sends incomplete partnerships to dashboard (not only a single page).
- **Signing**: **HTML contract** in a **new tab** (`/provider/partnership/contract`) with **drawn signature** + accept + **Send**; stores **PNG data URL** on `providers.partnership_provider_signature_data`.
- **Company**: Company admin opens **signed contract (HTML)** from provider list / partnership screen; sees terms + provider signature + timestamp.
- **Generated document**: `ProviderPartnershipService::generatedContractText()` builds a **simple, general** contract: **parties** (company, provider, email, role), **commission** (profile % + note on workspace tiers), then **general terms** (custom workspace text or `provider_partnership_default_terms` — still replace with lawyer text for production).

### 5. Email & queues (invites and notifications actually ship)
- **Partnership + marketing + invoice** mailables: **`ShouldQueue`** + **`$this->afterCommit = true`** so mail runs after DB commit.
- **`.env.example` + `config/flowdesk.php`**: Document **`MAIL_MAILER`** (SMTP / Resend / Postmark vs `log`), and **`QUEUE_CONNECTION`**: use **`sync`** for simple dev or run **`php artisan queue:work`** when using `database`/`redis`.

### 6. Internationalization
- **Partnership / contract / recruitment** strings added and mirrored in **`lang/fr.json`**, **`lang/es.json`**, **`lang/ar.json`** (not only English).

### 7. UI polish: fonts never “break”
- **`--flow-font-sans`** defined in **`resources/css/app.css` `:root`** so Tailwind **`.font-sans`** always has a valid variable; **`tailwind.config.js`** uses **`var(--flow-font-sans, ui-sans-serif)`** as extra safety. Tenant theme partial still overrides with `!important`.

### Suggested slide outline (from this file)
1. **Product**: Multi-tenant workspace — clients, projects, invoices, providers, forms/widget.
2. **Trust & onboarding**: Provider partnership contract with signature + company visibility; generated parties/commission/terms.
3. **Scale the client base**: Invite colleague → approval workflow; quick-create client on projects.
4. **Access control**: Portals show only permitted areas (client / provider / staff).
5. **Operations**: Queued mail + real SMTP/ESP configuration for production.
6. **Global**: FR / ES / AR + EN for key new flows.

### 8. Projects: agreed price → installments
- [x] **Client portal**: Shows negotiated/final agreed total; **Confirm price** sets `projects.client_price_confirmed_at`.
- [x] **Company project page** (after confirmation): **Installment payments** — due date, amount, **payment method for client** (bank transfer, card, PayPal, cash, other); sum vs agreed total warning; CRUD on `project_installments`.
- [x] **Client portal** (after confirmation): **Installment schedule** list with amounts, due dates, and method labels.
- [x] **API / routes**: `portal.projects.confirm-price`, `projects.installments.*`; tests in `tests/Feature/ProjectInstallmentsFlowTest.php`.

### 9. Provider recruitment: contract body + locale samples
- [x] Rich editor (Summernote) for `provider_partnership_terms`; placeholder tokens sidebar.
- [x] **FR / EN / ES / AR** buttons load `provider_partnership_default_terms` for that locale via `GET settings/provider-recruitment/sample-terms/{locale}` (safe HTML paragraphs).
- [x] Blade fix: sample URL map built in `@php` block (avoid `@json([...])` parse error on some compilers).

### 10. Providers list: signature visibility
- [x] **View provider signature** action → `providers.partnership.signature` (image + signed timestamp); alongside existing **View signed contract (HTML)**.

---

## Next session — **complete tomorrow**
- [ ] Smoke-test **client confirm price → installments** on staging (real tenant, portal + staff project page).
- [ ] Smoke-test **sample contract** buttons (all four locales) + Save on provider recruitment.
- [ ] Optional backlog: **mark installments as paid** (received date / link to invoice payment) — currently schedule-only.
- [ ] Optional: notify company when client confirms price (email or in-app).
- [ ] Run full **`./vendor/bin/pest`** + **`php artisan view:cache`** before tag/deploy.

---

## PHASE 17: Vision & go-to-market — Tunisia first, French for Europe, global

North star: **launch and win in Tunisia first**; use **French** (marketing, docs, demos) to sell across **Europe**; keep the product **international** (multi-locale, multi-currency, sector-agnostic) so **SMB through larger businesses** can adopt over time. Integrate with **automation platforms** (Zapier, Make, n8n, etc.) and **APIs companies already use**.

### Vision & positioning (keep honest + phased)
- [ ] Write a **short vision doc**: Tunisia → EU (FR) → worldwide; sectors served as **templates** over time (avoid claiming “perfect for every sector” on day one without vertical proof)
- [ ] **Primary beachhead** (first 12 months): still pick **one** Tunisian ICP to nail (e.g. agencies, services, networks) while the product stays general-purpose
- [ ] **Enterprise path**: document what “big business” needs later (SSO/SAML, SLA, dedicated support, custom contracts) vs. what ships for SMB first

### Tunisia-first launch
- [ ] Local **legal & fiscal** setup (Tunisia entity or compliant invoicing path with accountant)
- [ ] **TND + Flouci** as first-class in onboarding, demos, and help content; **Arabic (`ar`) + French (`fr`)** UX priority for TN market pages and support flows
- [ ] Demo scenario: **signup → tenant → form/widget → lead → project → proposal → invoice → Flouci and/or Stripe** in **French or Arabic** as chosen for the pitch

### Europe (French-facing, without limiting product to France)
- [ ] **Marketing site + pricing**: lead with **French** for EU inbound; offer **EN** (and others) as secondary
- [ ] **RGPD**: EU-appropriate hosting story, privacy policy, DPA for B2B, subprocessors list
- [ ] **EUR** plans, **SEPA** / card clarity in copy; roadmap note for **e-invoicing / local compliance** where EU customers require it

### Global product (all sectors, small → large)
- [ ] **Sector packs** (optional): starter templates per vertical (forms, proposal snippets, dashboard widgets) — ship incrementally instead of one giant “all sectors” launch
- [ ] **Scale story**: same codebase for SMB; **plan limits / Enterprise tier** for volume (users, projects, API rate limits) without forking the app
- [ ] **Locales & currency**: keep expanding from `config/flowdesk.php` + `lang/*` as you open regions

### Demo-ready happy path (any market)
- [ ] One **end-to-end** path polished: widget/form → submission → project → proposal → invoice → payment
- [ ] Staging walkthrough + **sample tenant** for sales; fix breaks on that path first

### Integrations — automation & marketing tools (Zapier-style)
- [ ] **Outbound webhooks** (tenant-configurable URLs + signing secret): e.g. `form.submission.created`, `invoice.paid`, `client.created` — enables Zapier/Make/n8n without building every connector first
- [ ] **Zapier app** (or **Make** scenario templates): start with 3–5 triggers + 2–3 actions (create client, create task, tag from submission)
- [ ] **Inbound actions** (secured API key per company, already partially via Sanctum/embed): document **REST** endpoints for “create lead / submission” from external tools
- [ ] Marketing stack **patterns** (not all at once): e.g. sync new submissions to **email lists** (Mailchimp/Brevo) or **CRM** (HubSpot/Pipedrive) via Zapier v1; native connectors later where ROI is clear

### Integrations — “APIs the company already uses”
- [ ] **Public API v1 scope**: CRUD for clients, projects, invoices (read + limited write) + **OAuth2 client credentials** or **long-lived scoped tokens** per company
- [ ] **Rate limits** + audit log entries for API calls (enterprise trust)
- [ ] **Integration directory** in app settings: “Connected apps”, webhook URLs, API key rotation
- [ ] Roadmap: **calendar** (Google/Microsoft), **accounting** (exports or partners), **Slack/Teams** notifications — prioritize by customer demand

### Go-to-market & traction
- [ ] Tunisia: first **pilots / paid** customers; collect testimonials and invoice/payment edge cases
- [ ] EU: French landing, **EUR** pricing, first inbound trials from FR (and FR-speaking BE/CH/LU if relevant)
- [ ] **~10** structured prospect conversations per wave; refine ICP from data, not assumptions

### “Smarter” after integration + revenue signal
- [ ] Replace **AI stubs** on workflows customers actually pay for; keep **human approval** for client-facing output
- [ ] **Automation rules** inside the app (optional): complement Zapier for users who do not use external stacks
