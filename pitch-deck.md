# FlowDesk — Pitch Deck Outline (10 slides)

> **Audience:** QDB, Invest Qatar, QFC, QSTP, angel / seed investors (GCC)  
> **Language:** English (slides) — speaker notes in EN with FR hints where useful  
> **Duration:** 10–12 minutes + 5 min Q&A  
> **Format:** One idea per slide. Max 6 bullets per slide. Use screenshots from live demo.

---

## Slide 1 — Title

**Headline:** FlowDesk  
**Subheadline:** The Arabic-ready operating system for service businesses — from first lead to paid invoice.

**On slide:**
- Logo + tagline
- Founder name & title
- `flowdesk.com` (or staging URL)
- *Seeking: [amount] QAR / USD — Seed / Pre-Series A*

**Speaker notes:**
> Open with one sentence: we replace 5–15 disconnected tools with one branded workspace per company. Mention Qatar regional HQ ambition in the first 30 seconds.

**Visual:** Product screenshot (dashboard) + map pin on Doha / GCC.

---

## Slide 2 — Problem

**Headline:** Service SMEs run on spreadsheets and email — and bleed time & cash.

**On slide:**
| Pain | Today | Cost |
|---|---|---|
| CRM | Excel, WhatsApp | Lost leads |
| Delivery | Trello, email | No link to billing |
| Quotes | Word / PDF | No audit trail |
| Invoicing | Separate tool | Late payments |
| Partners | Spreadsheets | Commission disputes |
| Clients | No portal | Heavy support load |
| Integrations | Manual CSV / Zapier hacks | Data silos |
| Vertical tools | One-off apps per sector | No link to CRM / billing |

**Stat (use local research or cite generically):**  
> Most GCC service SMEs still lack an integrated digital workflow from sale to payment.

**Speaker notes:**
> Emphasize **relationship-driven economies** (brokers, agencies, consultancies) where partner commissions and client transparency matter — poorly served by US-centric CRMs.

---

## Slide 3 — Solution

**Headline:** One workspace. Full revenue cycle. Your brand.

**On slide — the loop:**
```
Web form / widget → Lead → Project → Quote → Negotiation
    → Invoice → Payment → Client portal → Partner commission
         ↑              ↓                    ↓
    Nova AI      REST API / integrations   Vertical modules (zip / store)
         ↑
    Growth advisors · Reports · Marketing hub
```

**Four bullets:**
- **Branded tenant** — `company.flowdesk.com`, logo, theme, UI presets, **4 locales (EN, FR, ES, AR)** with full RTL
- **Three portals** — Staff workspace, client portal (incl. account requests), business-provider portal with **signed partnerships**
- **Nova AI** — voice navigation, live-data Q&A, voice briefing, growth advisors, AI invoice/quote dictation, optional **BYOK workspace agent**
- **Built for GCC** — multi-currency, regional payments (roadmap), vertical module packs, **REST API** for integrations

**Speaker notes:**
> This is not “another CRM.” It is the **operating system** for how service firms actually work in the Gulf — with an **AI layer** and **installable vertical packs** (real estate, delivery, POS…).

**Visual:** Simple flow diagram (above) + Nova mic + module sidebar screenshot.

---

## Slide 4 — Product (demo highlights)

**Headline:** Already built — advanced MVP, production-ready architecture.

**On slide — 10 capability tiles:**

| # | Tile | Highlights |
|---|---|---|
| 1 | **Clients & projects** | Pipeline, deadlines, **file vault** + shared links, tasks (kanban / Gantt), **installment schedules** |
| 2 | **Quotes & negotiation** | Line items, PDF, accept → project, provider commission splits, **document OCR scan** |
| 3 | **Invoicing & payments** | VAT, multi-currency, reminders, Stripe / PayPal / Flouci, online pay, **PDF template library** |
| 4 | **Client & provider portals** | Projects, invoices, task comments, commissions, payment requests, **client signup invitations** |
| 5 | **Growth & marketing** | Embeddable forms, **marketing hub** (widget + sitewide tracking), email marketing, sequences, open tracking |
| 6 | **Reports & analytics** | Revenue dashboards, payment channels, provider stats, **AI report counsel** + PDF export |
| 7 | **Nova AI assistant** | Voice wake (EN/FR/ES/AR), free voice nav, live-data chat, briefing, **growth advisors**, writing modes |
| 8 | **Module marketplace** | Public catalog, cart/checkout, buy/install vertical `.zip` packs (Qatar real estate…), CRM-linked settings |
| 9 | **Calendar & messaging** | Workspace calendar, **Google sync**, Calendly embed, portal calendar, internal client/provider **chat** |
| 10 | **Integrations & security** | **REST API v1** (clients, projects, invoices, bulk import), 2FA, IP allowlist, audit logs, RBAC |

**Callouts:**
- Multi-tenant SaaS · Laravel · **Arabic-first RTL** (tables, numbers, Nova voice in AR)
- **Workspace AI agent (BYOK)** — Pro/Enterprise can plug own OpenAI / Claude / Gemini keys
- **Premium Nova voice** — Gemini Flash TTS or OpenAI TTS on paid plans
- **Landing page builder** — GrapesJS visual editor + AI landing-page writing mode (optional)
- **Customizable dashboard** — drag widgets, saved UI presets, theme library

**Speaker notes:**
> Demo path: say **“Nova”** → ask *“What revenue this month?”* → open **Analytics** → show **API Connect** token → install **Qatar Real Estate** module with viewings on calendar. Keep under 2 minutes if live.

**Visual:** 10 icons or annotated screenshots + Nova tooltip + API docs screenshot.

---

## Slide 5 — Nova AI (deep dive — optional backup slide)

**Headline:** Nova — your company’s AI co-pilot (not a generic chatbot).

**On slide:**
| Mode | What it does | Credits |
|---|---|---|
| **Voice navigation** | “Nova → invoices / report / new client” | Free |
| **Wake + voice reply** | “Yes, what would you like?” — **Premium TTS** (Gemini / OpenAI) | ~5 / reply |
| **Workspace chat** | Revenue, clients, unpaid invoices, projects, calendar | ~75 / message |
| **Voice briefing** | Full spoken business summary (revenue, tasks, tickets…) | ~15 |
| **AI forms** | Say “new invoice” → voice dictation → AI line items + **OCR scan** | Plan credits |
| **Writing modes** | Proposals, pricing, emails, SEO, tickets, landing pages | Plan credits |
| **Growth advisors** | Live-data AI on **projects, invoices, clients** pipeline health | Plan credits |
| **Workspace AI agent** | Pro/Enterprise: **your own API keys** for thinking & analysis | BYOK |

**Differentiators:**
- Answers from **live workspace data** + general business advice — not a generic chatbot
- **Arabic wake word** (`نوفا`, `يا نوفا`) + browser voice fallback when credits exhausted
- **AI report counsel** — ask Nova to interpret reports and export PDF advice
- OpenAI / Claude / **Gemini** (platform-configurable + optional workspace BYOK)

**Speaker notes:**
> Nova is a **retention & upsell engine** — drives daily usage and AI credit revenue without replacing the core CRM.

**Visual:** Top-bar Nova mic + assistant page with example questions.

---

## Slide 6 — Module ecosystem (deep dive — optional backup slide)

**Headline:** Vertical packs — extend FlowDesk without forking the platform.

**On slide:**
```
Core CRM (clients, projects, invoices, calendar)
        ↕ FK + navigation links
Module zip (module_* tables, Blade views, migrations)
        ↕
Marketplace store → purchase → install per workspace
```

**Live examples (Qatar pack):**
- **Property listings** — zones, QAR pricing, owner client link
- **Viewings** — schedule visits, sync to workspace calendar
- **Broker commissions** — deal splits linked to providers
- **Bundles** — one zip = sector starter pack

**Business angle:**
- **SaaS upsell** — module subscriptions via marketplace + **public storefront** (cart, checkout)
- **Partner channel** — certified third-party vertical developers
- **Faster GTM** — ship Qatar real estate / delivery / POS without rebuilding core
- **API-ready** — modules link to core via FK; external systems via **REST API v1**

**Speaker notes:**
> Modules are **not separate apps** — they plug into existing clients, projects, invoices, and calendar. See `MODULE.md` + `modules-qatar.md`.

**Visual:** Settings → Modules screenshot + marketplace catalog + checkout flow.

---

## Slide 6b — Integrations & API (optional backup slide)

**Headline:** Connect FlowDesk to your stack — without losing the single source of truth.

**On slide:**
| Integration | What it enables |
|---|---|
| **REST API v1** | Create/list clients, projects, invoices; bulk import from legacy CRM |
| **Embed widgets** | Lead forms + marketing tracker on any website |
| **Webhooks** | Stripe billing events (live); payment/invoice webhooks (roadmap) |
| **Google Calendar** | Two-way sync for deadlines, viewings, installments |
| **Calendly** | Embed scheduling on workspace calendar page |
| **OAuth login** | Google / social sign-in for faster onboarding |
| **Workspace AI agent** | Bring your own OpenAI / Claude / Gemini keys (Pro+) |

**Speaker notes:**
> Position API for **integrators and mid-market** — agencies connecting Shopify, legacy Excel, or internal tools. Token is per-tenant, encrypted, documented in-app.

**Visual:** Settings → API Connect page with endpoint table + curl example.

---

## Slide 7 — Why Qatar / Why now

**Headline:** Qatar as our GCC hub — aligned with Vision 2030.

**On slide:**
- **Vision 2030** — SME digitization, knowledge economy, regional hub
- **Market gap** — Global tools (Salesforce, Zoho) are expensive, English-first, weak on Arabic UX & broker networks
- **Our fit** — Arabic-native, end-to-end, white-label workspaces + **Nova AI** + **sector modules**
- **Local impact** — Jobs (Qatarization), local entity, regional data hosting

**Three vertical wedges (Year 1):**
1. Digital & creative agencies (Doha)
2. Management consultancies
3. Broker / referral networks (**real estate module pack live**)

**Speaker notes:**
> Position Qatar as **launch pad for GCC**, not only local market. Mention target programs: QDB, QFC, Invest Qatar — only if applying.

---

## Slide 8 — Business model

**Headline:** Predictable SaaS revenue + usage & ecosystem upside.

**On slide:**

| Stream | Description |
|---|---|
| **Subscriptions** | Starter / Pro / Enterprise — **15 gated features** (projects, AI, analytics, modules…) |
| **Add-ons** | Priority support, extra form submissions, calendar, modules |
| **AI usage** | Nova chat, voice, briefing, growth advisors, writing modes — credits beyond plan |
| **Module marketplace** | Paid vertical packs (monthly / one-time) — platform share + public storefront |
| **Future** | Enterprise SSO, custom SLA, integration services, API usage tiers, transaction fee on provider marketplace |

**Example pricing (illustrative — customize):**
| Plan | USD / month | Includes |
|---|---|---|
| Starter | ~$29 | 5 users, 10 projects, forms, basic AI credits, core CRM |
| Pro | ~$79 | 25 users, providers, marketing hub, email marketing, analytics, reports, calendar, modules, **BYOK AI agent**, premium voice |
| Enterprise | ~$199 | Unlimited quotas, all features, dedicated success, custom SLA |

**Speaker notes:**
> Anchor vs cost of 4–5 separate tools + vertical app. **Nova credits** and **module subscriptions** add ARPU without new sales motion.

---

## Slide 9 — Traction & roadmap

**Headline:** Product shipped — now anchoring in Qatar.

**On slide — Today (✅ shipped):**
- Multi-tenant platform live (subdomains, Stripe billing portal, 3 portals)
- Payments: Stripe, PayPal, Flouci + regional gateway path
- **Arabic UI + full RTL** (navigation, tables, numbers, Nova voice in AR)
- Provider commissions, **signed partnership contracts**, remittance requests
- **Nova AI** — voice nav, chat, briefing, OCR scan, growth advisors, writing modes, premium TTS
- **Workspace AI agent (BYOK)** + **REST API v1** (clients, projects, invoices, bulk import)
- **Reports & analytics** dashboards with AI counsel + PDF export
- **Marketing hub** — widget traffic + sitewide pageview tracking, email marketing + sequences
- **Module marketplace** — public catalog, cart/checkout, install, Qatar real-estate pack
- **Calendar** — workspace + Google sync + Calendly embed + module viewing sync
- **Security** — 2FA, tenant IP allowlist, audit logs, encrypted API tokens
- Internal **client/provider messaging**, support tickets, customizable dashboard
- 🔄 First Qatar pilots — *[names / LOIs if available]*

**12-month roadmap:**

| Quarter | Milestone |
|---|---|
| **Q1** | Qatar entity (LLC/QFC), QAR, 5 beta SMEs, 3 paid module packs |
| **Q2** | GCC payment integration, 20 paying customers, certified module partners |
| **Q3** | VAT-compliant invoicing, channel partner, Nova mobile polish |
| **Q4** | UAE/KSA expansion, SSO, ARR target **[X]** |

**Speaker notes:**
> Be honest: traction is **product-led** today; investment funds **go-to-market + localization + module catalog**, not core build from zero.

---

## Slide 10 — Go-to-market (Qatar)

**Headline:** Land in Doha. Expand across GCC.

**On slide:**
- **Direct sales** — Founders + 1–2 BDRs (Arabic + English)
- **Partners** — IT integrators, chambers, accelerators, **module developers**
- **Product-led** — Free trial → branded workspace in minutes; **Nova wow moment** on first login
- **Vertical demos** — Agency, consultancy, **real-estate broker** (live module pack)

**Channels:**
- LinkedIn + WhatsApp Business (GCC norm)
- Events: QSTP, Web Summit Qatar, sector meetups
- Referrals via business-provider network feature (dogfooding)
- **Module marketplace** as content marketing (sector landing pages)

**Speaker notes:**
> Qatarization plan: hire local support/sales within 6 months — state headcount targets if known.

---

## Slide 11 — Team & ask

**Headline:** The team & what we’re raising.

**On slide — Team (fill in):**
| Name | Role | Relevant background |
|---|---|---|
| [Founder] | CEO / Product | [SaaS, MENA, domain] |
| [CTO] | Engineering | [Laravel, scale] |
| [Advisor] | Qatar / GCC | [Optional — local network] |

**The ask:**
- **Raising:** [e.g. 1.5M – 3M QAR / $400K – $800K USD]
- **Instrument:** Equity / SAFE / QDB convertible — *[as applicable]*
- **Runway:** 18–24 months

**Use of funds:**
| Allocation | % |
|---|---|
| Local team (sales, support, dev) | 40% |
| Product (QAR, GCC payments, modules, Nova) | 25% |
| Marketing & partnerships | 20% |
| Legal, hosting, compliance | 10% |
| Working capital | 5% |

**Speaker notes:**
> Close with clarity: money → entity → pilots → ARR → GCC scale.

---

## Slide 12 — Vision & close

**Headline:** Every service business in the GCC on one FlowDesk.

**On slide:**
- **2026** — Qatar HQ, 50+ paying workspaces, 10+ marketplace modules
- **2027** — GCC payments & compliance standard, Nova as daily co-pilot
- **2028** — Category leader for Arabic-first B2B operations platform + vertical ecosystem

**Why us:**
- End-to-end (not point solution)
- Arabic + white-label (not US retrofit)
- Broker / provider layer with **e-signed partnerships** (Gulf-specific)
- **Nova AI** on live business data + **growth advisors** (not generic ChatGPT wrapper)
- **Modular & marketplace-ready** + **REST API** for integrators
- **Enterprise-ready security** — 2FA, IP allowlist, audit trail

**Close line:**  
> *We’re building the system of record for how service businesses in the Gulf win clients, deliver work, and get paid — with AI and sector packs built in — starting from Qatar.*

**Contact:**  
[email] · [phone] · [LinkedIn] · [demo link]

**Speaker notes:**
> End with **Nova voice demo** + real-estate module. Leave one-pager (`executive-summary-en.md`) and `idea-en.md`.

---

## Appendix slides (optional, not presented)

**A1 — Competition**

| | FlowDesk | Zoho | HubSpot | Odoo |
|---|---|---|---|---|
| End-to-end quote→pay | ✅ | Partial | ❌ | Partial |
| Arabic-first UX (full RTL) | ✅ | Weak | ❌ | Partial |
| Voice AI on live data | ✅ | ❌ | ❌ | ❌ |
| AI growth advisors (live data) | ✅ | ❌ | ❌ | ❌ |
| Client portal | ✅ | Add-on | ❌ | Extra |
| Broker commissions + e-sign | ✅ | ❌ | ❌ | Custom |
| White-label subdomain | ✅ | ❌ | ❌ | Self-host |
| Vertical module store | ✅ | Marketplace apps | ❌ | Apps |
| Workspace REST API | ✅ | ✅ | ✅ | ✅ |
| BYOK AI agent (enterprise) | ✅ | ❌ | ❌ | ❌ |
| SMB price point | ✅ | ✅ | ❌ | ✅ |

**A2 — Tech stack & security**  
Laravel 13, multi-tenant isolation, **REST API v1**, 2FA, audit logs, IP allowlist, encrypted API tokens, module zip security scanner, queued mail, backups, Sentry.

**A3 — Nova AI feature matrix**  
Voice nav (free) · Chat (live data) · Briefing · Growth advisors (projects/invoices/clients) · AI invoice/quote OCR scan · Writing modes (sales, comms, content, growth) · Premium TTS · Workspace BYOK agent · Gemini/OpenAI/Claude · 4-language wake word.

**A4 — Workspace API (v1)**  
`GET/POST /api/v1/workspace/clients` · projects · invoices · bulk `POST /import` · Bearer token per tenant · documented in Settings → API Connect.

**A5 — Module catalog (Qatar)**  
See `modules-qatar.md` — real estate, delivery, POS, e-commerce, document expiry, 20+ vertical ideas; `qatar-real-estate` pack live.

**A6 — Financial model (3-year)**  
Attach spreadsheet: conservative / base / optimistic scenarios.

**A7 — Qatar localization checklist**  
QAR, QA country, GCC gateways, VAT 5%, data residency, NDPR alignment.

---

## Demo script (5 min — for video or live)

1. **0:00–0:30** — Register company → subdomain live → **Arabic UI** toggle (show RTL tables + numbers)  
2. **0:30–1:00** — Say **“Nova”** → wake reply → ask *“What revenue this month?”* → spoken + written answer  
3. **1:00–1:45** — Embed form → submission → create project → add tasks (kanban) → **file vault** upload  
4. **1:45–2:30** — Create quote → **AI voice dictation** or **document OCR scan** → send → client portal view  
5. **2:30–3:00** — Convert to invoice → pay (Stripe test) → show **installment schedule** on project  
6. **3:00–3:30** — **Analytics** dashboard → **Reports** with AI counsel → copy **API token** (Settings → API Connect)  
7. **3:30–4:00** — **Install Qatar Real Estate module** → property listing → schedule viewing → calendar  
8. **4:00–4:30** — Provider **signed partnership** → commission dashboard → payment request inbox  
9. **4:30–5:00** — **Growth advisor** on unpaid invoices → email marketing sequence preview

---

## Files in this repo

| File | Purpose |
|---|---|
| `idea.md` | Full concept (French) + Qatar adaptation |
| `idea-en.md` | Full concept (English) for investors |
| `executive-summary-en.md` | One-page leave-behind |
| `pitch-deck.md` | This outline |
| `NOVA.md` | Nova assistant & voice — full feature doc |
| `MODULE.md` | How to build installable modules |
| `modules-qatar.md` | Qatar vertical module catalog |
| `modules-international.md` | International module catalog |
| `auth-roles.mdc` | RBAC roles & permissions reference |

---

*Customize bracketed fields [X] before submitting to any program.*
