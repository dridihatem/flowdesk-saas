# FlowDesk — Product Vision & Qatar Investment Positioning

> Investor / incubator brief for programs in **Qatar & GCC** (QDB, Invest Qatar, QFC, QSTP).  
> **Current product:** multi-tenant B2B SaaS — from first lead to paid invoice.

**Related documents:** [`pitch-deck.md`](pitch-deck.md) · [`executive-summary-en.md`](executive-summary-en.md) · [`idea.md`](idea.md) (French)

---

## 1. One-liner

**FlowDesk** is an **all-in-one workspace** for service businesses (agencies, consultancies, broker networks, professional services): clients, projects, quotes, invoices, payments, client portal, business providers, web forms, email marketing, and AI — each company on **its own subdomain** (`your-company.flowdesk.com`).

---

## 2. The problem

Service SMEs and mid-market firms juggle **5 to 15 disconnected tools**:

| Business need | Typical tool | Consequence |
|---|---|---|
| CRM & clients | Excel, HubSpot, Notion | Scattered data |
| Delivery | Trello, Asana | No billing link |
| Quotes & negotiation | Word, PDF, email | No audit trail |
| Invoicing | QuickBooks, local tool | Currency, VAT, manual reminders |
| Online payments | Stripe, local gateway | Custom integration |
| Partners / commissions | Spreadsheets | Errors, disputes |
| Client portal | None or SharePoint | High support load |
| Web leads | Google Forms | No structured qualification |

**Result:** wasted time, delayed payments, weak pipeline visibility, hard to scale beyond 10–50 people.

In **GCC markets**, this is worse: relationship-driven sales, **referral brokers**, Arabic-first teams, and clients who expect **WhatsApp-era transparency** — yet most software is US/EU-centric.

---

## 3. The FlowDesk solution

A **single multi-tenant SaaS platform** where each company gets:

- **Isolated workspace** (subdomain, branding, theme, locale)
- **Full commercial cycle:** lead → project → quote → negotiation → invoice → payment
- **Dedicated portals:** client, business provider (referral partner)
- **Automation:** invoice reminders, email campaigns, AI assistant, webhooks (roadmap)
- **International-ready architecture:** 4 languages (EN, FR, ES, **AR**), multi-currency, RBAC

### Investor demo path

```
Embedded web form
    → Lead (inquiry)
    → Project + tasks (kanban, Gantt, client ↔ team comments)
    → Quote (proposal) + negotiation
    → Invoice + payment (Stripe / regional gateway)
    → Client portal (tracking, installments, payment)
    → Business-provider commission (if applicable)
```

---

## 4. Product pillars (current state)

| Pillar | Capabilities |
|---|---|
| **Multi-tenant** | Subdomains, per-tenant storage, company API token |
| **Light CRM** | Clients, projects, statuses, deadlines, secure file vault |
| **Commercial** | Quotes, line items, validity, invoice conversion, negotiation |
| **Invoicing** | Multi-currency (USD, EUR, GBP, TND), VAT, stamp duty, PDF, reminders |
| **Payments** | Stripe, PayPal, Flouci (Tunisia), manual recording |
| **Business providers** | Signed partnership, per-project commissions, payment requests |
| **Portals** | Client (projects, invoices, task comments); Provider (commissions) |
| **Marketing** | Embeddable forms, email marketing, sequences, open tracking |
| **AI** | Assistant, quote/invoice/campaign generation (plan credits) |
| **Security** | Admin 2FA, IP allowlist, audit logs, Spatie RBAC |
| **Extensibility** | Zip-installable modules per workspace |
| **Subscription** | Starter / Pro / Enterprise plans + Stripe billing |

**Stack:** Laravel 13, MySQL, Tailwind, Alpine.js, queues, backups, Sentry.

---

## 5. Business model

| Revenue stream | Description |
|---|---|
| **SaaS subscription** | Monthly plans (users, projects, forms, AI credits) |
| **Add-ons** | Priority support, form volume, etc. |
| **AI usage** | Credits above plan allowance |
| **Platform fee** (optional) | % on transactions if provider marketplace scales |

**Target customers:** digital agencies, consultancies, construction services, broker networks, service franchises — **Qatar & GCC SMEs** digitizing sales, delivery, and billing.

---

## 6. Current geography vs Qatar pivot

| Today (code & GTM) | Natural fit for Qatar |
|---|---|
| Tunisia pilot (Flouci, TND) | MENA launch experience |
| French + Arabic | **GCC-aligned** |
| USD / EUR pricing | Add **QAR** |
| Stripe global | Add **GCC payment gateways** |
| Generic hosting | **Qatar / GCC data residency** |

FlowDesk is a **horizontal platform** with optional **sector packs** — suitable for a regional hub strategy centered in Qatar.

---

## 7. Why Qatar? (investment angle)

Qatar’s **National Vision 2030** emphasizes economic diversification, **SME digitization**, entrepreneurship, and a regional business hub.

FlowDesk can position as:

1. **Software infrastructure for Qatari SMEs** — replace fragmented foreign tools with **Arabic + English**, regionally hosted software.
2. **Export-of-services enabler** — Qatari firms billing GCC / international clients (multi-currency, client portal).
3. **Broker network platform** — commissions, signed contracts, audit trail (real estate, consulting, events, B2B services).
4. **“Made in Qatar” SaaS** — local IP, tech jobs (Qatarization), partnerships with QDB, QFC, QSTP, Invest Qatar.

**Programs to consider:**

- **QDB** — startup & SME tech funding / acceleration  
- **QFC** — tech holding structure, regional market access  
- **QSTP** — R&D, AI, integrations  
- **MCIT / digital incubation** — innovation programs  
- **Invest Qatar** — foreign investment + local anchoring  

*(Validate against specific program requirements and stage: idea, MVP, first paying customers.)*

---

## 8. What to change for Qatar

### 8.1 Product & compliance (high priority)

| Change | Detail | Effort |
|---|---|---|
| **QAR currency** | Add to `config/flowdesk.php`, labels, plans, invoices | Low |
| **Qatar country** | `QA` → `QAR` in `country_currency`, registration | Low |
| **Arabic (Qatar)** | Strengthen `ar`: local terminology, optional Hijri dates | Medium |
| **English** | Default for B2B / investor-facing flows | Done |
| **GCC payment gateways** | Skip Cash, QPay, Tap Payments, PayFort, local banks | Medium–high |
| **Legal invoicing** | Qatar VAT (5%), commercial registration fields | Medium + local accountant |
| **E-invoicing** | Track FTA / future mandates | Roadmap |
| **Data residency** | Host in **Qatar or GCC** | Infra + legal |
| **Compliance** | Privacy policy, DPA, **Qatar NDPR** / GCC best practice | Legal |

### 8.2 Qatar go-to-market

| Change | Detail |
|---|---|
| **Local entity** | Qatar LLC or QFC — often required by programs |
| **QAR pricing** | Plans in riyals; USD option for international clients |
| **Marketing site** | `/qa` or `.qa` domain: Vision 2030, target sectors |
| **Local use cases** | 3 demos: Doha digital agency, consultancy, real-estate broker network |
| **Partnerships** | Chambers, accelerators, local IT integrators |
| **Support** | GST hours, WhatsApp Business, **AR + EN** support |
| **Qatarization** | Local hiring plan (sales, support, dev) |

### 8.3 Enterprise trust

| Change | Detail |
|---|---|
| **Enterprise SSO** | SAML / Azure AD |
| **SLA tier** | Documented Enterprise support |
| **Public API v1** | Webhooks + REST (Zapier, local tools) |
| **Export reports** | Excel/PDF for partners or authorities |
| **High availability** | Multi-AZ, monitoring, DR plan |

### 8.4 Investor narrative shift

| Before | After (Qatar) |
|---|---|
| “Tunisia first, Europe FR” | **“GCC hub from Qatar — Arabic-first SME OS”** |
| “Flouci + Stripe” | **“Regional payments (QAR, GCC) + Stripe international”** |
| “Multi-sector global” | **“3 Qatar verticals: agencies, consulting, broker networks”** |
| “Side project / MVP” | **“Team + entity + pilots with measurable ARR in 12 months”** |

---

## 9. Investment proposition

### Problem in Qatar
> Most service SMEs still run on email and Excel. Global platforms (Salesforce, Zoho) are costly, English-first, and lack integrated client portals and **referral-partner commission** workflows — critical in Gulf relationship economies.

### Solution
> FlowDesk: **one branded workspace per company**, Arabic and English, lead to payment, with client and partner portals — **hosted in the region**.

### 12-month traction plan (honest)
- **M0–M3:** Qatar entity, QAR, pilot payments, 5 beta companies  
- **M4–M6:** 20 paying customers, 1 integrator partner  
- **M7–M12:** GCC expansion (UAE, KSA), invoice compliance module, SSO  

### Use of funds (illustrative)

| Category | % |
|---|---|
| Local team (sales, support, dev) | 40% |
| Product (GCC payments, compliance, infra) | 25% |
| Marketing & Qatar partnerships | 20% |
| Legal, compliance, hosting | 10% |
| Working capital | 5% |

### Investor KPIs
- MRR / ARR  
- Active paying workspaces  
- Trial → paid conversion  
- Invoice GMV through platform  
- Client portal NPS  
- % of customers using Arabic  

---

## 10. Defensible advantages

1. **End-to-end workflow** (not CRM-only or invoicing-only)  
2. **Multi-tenant white-label** (subdomain, theme, PDF) — franchises / groups  
3. **Client & provider portals** — rare in mid-market alternatives  
4. **Native Arabic** — vs US/EU retrofits  
5. **Modular architecture** — zip modules, API, webhooks  
6. **SMB-friendly pricing** — vs Salesforce / Dynamics  

---

## 11. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Crowded market (Zoho, Odoo) | **GCC niche:** Arabic, brokers, portal, local payments |
| Invoice / data compliance | Qatari accounting partner + certified host |
| Founder dependency | Early team + documentation |
| AI still maturing | AI only on paid workflows customers use |
| Local entity requirement | QFC or LLC at application stage |

---

## 12. Submission checklist (Qatar)

- [ ] 10-slide pitch deck ([`pitch-deck.md`](pitch-deck.md))  
- [ ] 3-minute demo video (lead → payment)  
- [ ] One-pager ([`executive-summary-en.md`](executive-summary-en.md))  
- [ ] 3-year financial model (conservative / base)  
- [ ] Legal structure (Qatar LLC vs QFC)  
- [ ] LOIs from 2–3 Qatari pilot companies  
- [ ] Qatarization & local impact plan  
- [ ] Vision 2030 alignment statement  
- [ ] Dated product roadmap (QAR, payments, data residency)  

---

## 13. Executive summary

**FlowDesk** is a multi-tenant B2B SaaS platform that lets service companies run their **entire commercial chain** — clients, projects, quotes, invoices, payments, portals, and partner commissions — on a branded, multilingual workspace (including Arabic).

We seek to **establish our regional hub in Qatar** to serve GCC SMEs and broker networks, with local compliance, QAR payments, and regional hosting. The product is **already functional** (advanced MVP); investment will fund **Qatar anchoring**, **local integrations**, and **first paying customers**.

---

*Based on the actual FlowDesk SaaS codebase — customize raise amount, team, and target program before submission.*
