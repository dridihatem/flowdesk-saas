
# Flowdesk SaaS - International Multi-Sector Tasks.md

## PHASE 1: Project Setup
- [x] Initialize Laravel project (Laravel 13 — current `laravel/laravel` skeleton; tasks originally said 11)
- [x] Database Mysql host: 127.0.0.1, port: 8889, database: flowdesksaas, user and password: root
- [x] Configure .env (DB, APP_URL, CACHE, QUEUE, STORAGE)
- [x] Install TailwindCSS + Alpine.js / Vue.js (Breeze Blade + Alpine; Vue 3 island on dashboard)
- [x] Setup Git repository and CI/CD pipeline
- [x] push to the git repo https://github.com/dridihatem/flowdesk-saas.git
- [x] Create base layout (auth + dashboard)
- [ ] Setup Figma design system (UI kit, components, colors, typography) — *code tokens in `resources/css/app.css`; create the Figma library separately and sync names*

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
- [ ] Optional 2FA for company admins
- [x] JWT/Sanctum for API authentication (Sanctum — `GET /api/user` with Bearer token)

## PHASE 4: Internationalization & Currency
- [x] Multi-language support (i18n): English, French, Spanish, Arabic (`lang/*.json`, `SetLocale` middleware, `users.locale`)
- [x] Language selector in dashboard
- [x] Multi-currency support and auto-selection based on company country (`companies.country`, `config/flowdesk.country_currency`)
- [x] Currency conversion logic for TND, USD, EUR, etc. (`App\Services\CurrencyConverter`, `config/currencies.php`)

## Social login (OAuth)
- [x] Laravel Socialite — GitHub, Google, LinkedIn OpenID (`SocialAuthController`, `oauth/company` completion for new tenants)

## PHASE 5: Core Database Models
- [ ] `companies`
- [ ] `company_settings` (branding, SMTP, theme, layout, colors, payment credentials)
- [ ] `users` + roles
- [ ] `clients`
- [ ] `providers`
- [ ] `projects`
- [ ] `forms` & `form_fields`
- [ ] `form_submissions`
- [ ] `proposals` / `estimates`
- [ ] `invoices`
- [ ] `payments` + `transactions`
- [ ] `payment_methods`
- [ ] `subscriptions` & `plans`
- [ ] `plan_limits`
- [ ] `usage_tracking`
- [ ] `marketing_support`
- [ ] `negotiations`
- [ ] `audit_logs`

## PHASE 6: UI SYSTEM & THEMING ENGINE 🎨

### Theme Engine Core
- [ ] Create dynamic theme system per company
- [ ] Store theme settings in `company_settings`
- [ ] Fields:
  - theme_name
  - layout_type (sidebar / topbar / minimal)
  - primary_color
  - secondary_color
  - font_family
  - dark_mode
  - logo

### CSS Dynamic System
- [ ] Use CSS variables for colors
- [ ] Inject dynamic styles from database into layout
- [ ] Support real-time UI updates without reload

### Theme Loader Logic
- [ ] Load theme dynamically:
  `themes/{theme}/layouts/{layout}.blade.php`
- [ ] Fallback to default theme

### Pre-built Themes
- [ ] Default (clean SaaS UI)
- [ ] Dark Pro (dark mode)
- [ ] Minimal (topbar layout)
- [ ] Luxury (premium design)
- [ ] Admin/Data-heavy dashboard

### Layout System
- [ ] Sidebar layout
- [ ] Top navigation layout
- [ ] Minimal layout
- [ ] Responsive mobile layout

### UI Components Library
- [ ] Cards (stats, analytics)
- [ ] Tables (clients, projects, invoices)
- [ ] Forms (builder + inputs)
- [ ] Modals & alerts
- [ ] Buttons & badges
- [ ] Charts (revenue, analytics)

### Company Customization Panel
- [ ] Theme selector UI
- [ ] Color picker (primary / secondary)
- [ ] Logo upload
- [ ] Layout selection
- [ ] Dark mode toggle
- [ ] Font selection

### Advanced Customization (Pro/Enterprise)
- [ ] Custom CSS editor
- [ ] Custom dashboard widgets
- [ ] Drag & drop dashboard layout
- [ ] Save multiple UI presets per company

## PHASE 7: Company Dashboard
- [ ] Dashboard homepage (stats + KPIs)
- [ ] Project list with filters & search
- [ ] Clients management
- [ ] Providers management + commission tracking
- [ ] Forms builder UI
- [ ] Widget generator
- [ ] Branding settings panel
- [ ] Analytics dashboard
- [ ] Notifications & alerts
- [ ] AI assistant panel

## PHASE 8: Form Builder & Widget System
- [ ] Create simple or wizard forms
- [ ] Drag & drop fields
- [ ] Multi-step forms
- [ ] Required validation
- [ ] Generate embeddable JS widget
- [ ] Token-based submission authentication
- [ ] Widget customization (color, theme)
- [ ] Widget analytics tracking
- [ ] Versioning system for widgets

## PHASE 9: Project & Provider Management
- [ ] Create projects manually or via forms
- [ ] Assign providers
- [ ] Provider dashboard
- [ ] Provider can create projects
- [ ] Provider can send estimates
- [ ] Negotiation workflow:
  - submitted
  - counter-offer
  - accepted
  - rejected
- [ ] Commission tracking per deal
- [ ] Activity logs

## PHASE 10: Proposals & Invoices
- [ ] Proposal creation & editing
- [ ] Convert proposal → invoice
- [ ] Invoice statuses
- [ ] PDF generation (custom design)
- [ ] Email sending via SMTP
- [ ] Custom templates per company
- [ ] Invoice reminders

## PHASE 11: Payments
- [ ] Stripe integration
- [ ] PayPal integration
- [ ] Bank transfer
- [ ] Konnect/Mobibank (Tunisia)
- [ ] Company payment credentials
- [ ] Webhook system
- [ ] Payment status sync
- [ ] Transaction logs

## PHASE 12: Subscription & Plans
- [ ] Plans: Basic / Pro / Enterprise
- [ ] Feature limits:
  - users
  - projects
  - forms
  - submissions
  - widgets
  - AI credits
- [ ] Subscription system (Cashier)
- [ ] Trial management
- [ ] Usage tracking
- [ ] Add-ons system

## PHASE 13: Monetization
- [ ] Subscription billing
- [ ] Commission system
- [ ] Pay-per-use features
- [ ] Billing dashboard
- [ ] Revenue analytics

## PHASE 14: AI Integration
- [ ] AI proposal generator
- [ ] Pricing suggestions
- [ ] Auto form generator
- [ ] Project summarization
- [ ] AI usage tracking

## PHASE 15: Security
- [ ] Data encryption
- [ ] API token validation
- [ ] Rate limiting
- [ ] RBAC
- [ ] Audit logs
- [ ] Optional IP restriction

## PHASE 16: Background Jobs & Monitoring
- [ ] Email queues
- [ ] Payment webhooks
- [ ] AI processing
- [ ] Cron jobs
- [ ] Logging system
- [ ] Backup system
- [ ] Error monitoring (Sentry)

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