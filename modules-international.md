# FlowDesk — International module catalog

> Installable module ideas (`.zip` via **Settings → Modules**) for **Canada, France, USA, Africa**, and other markets.  
> Same technical model as [`MODULE.md`](MODULE.md) and regional deep-dives like [`modules-qatar.md`](modules-qatar.md).

---

## How vertical modules extend FlowDesk

Modules **extend** the CRM — they do not replace it.

| Core entity | Role across regions |
|---|---|
| **Clients** | Buyer, tenant, patient, merchant, donor, member |
| **Projects** | Mandate, job site, campaign, delivery route, case file |
| **Quotes / invoices** | Estimate, order, POS ticket, subscription, grant claim |
| **Providers** | Agents, affiliates, subcontractors, referral partners |
| **Forms** | Lead capture, intake, registration, application |
| **Client portal** | Order tracking, payments, document upload, status |
| **Payments** | Local currency + Stripe + regional gateways (core) |

```
Vertical module (.zip)
    ↔ module_* tables (listings, stock, routes…)
    ↔ views /modules/{slug}
    ↔ links to clients / projects / invoices / forms
```

**Zip module limit:** Blade + DB only (no PHP controllers). For real-time POS, GPS, marketplace APIs → start with **v1 CRUD + reports**, then promote hot verticals into **core APIs**.

---

## Regional adaptation matrix

| Region | Currency / tax | Compliance notes | High-demand verticals |
|---|---|---|---|
| **Canada** | CAD, GST/HST/PST | Bilingual EN/FR, provincial tax rules | Trades, property mgmt, SaaS, retail |
| **France / EU** | EUR, TVA | Facture électronique, RGPD, URSSAF-style services | Agencies, construction, health/beauty, B2B services |
| **USA** | USD, state sales tax | 1099 contractors, LLC-friendly workflows | Home services, real estate, e-com, clinics |
| **North Africa** | TND, MAD, DZD, EGP | Stamp duty, bilingual AR/FR, cash/COD | Retail, delivery, agencies, import/export |
| **Sub-Saharan Africa** | XOF, XAF, NGN, ZAR, KES | Mobile money, COD, multi-currency | Logistics, agri, schools, field services |
| **GCC / Qatar** | QAR, VAT 5% | See [`modules-qatar.md`](modules-qatar.md) | Real estate, delivery, POS, SaaS |

Use **slug prefix by market** only when the module is truly local (e.g. `ca-hst-helper`, `fr-facture-electronique`). Prefer **neutral slugs** (`property-listings`, `pos-register`) + **locale packs** in `module.json` when the logic is the same everywhere.

---

## Priority by market (P0 = build first)

### Canada

| Priority | Vertical | Why |
|---|---|---|
| P0 | Home services / trades | Huge SMB segment; quotes → jobs → invoices |
| P0 | Property management | Condos, rentals, maintenance tickets |
| P1 | Retail POS + e-com lite | CAD, provincial tax display |
| P1 | Professional services | Retainers, T&M, bilingual docs |
| P2 | Snow / seasonal ops | Route planning, contract renewals |

### France & EU

| Priority | Vertical | Why |
|---|---|---|
| P0 | BTP / construction léger | Devis, acomptes, situations de travaux |
| P0 | Agences (marketing, conseil) | Forfaits, TJM, propositions |
| P1 | Immobilier (gestion locative) | Baux, quittances, états des lieux |
| P1 | Salon / clinique (non-EMR) | RDV + facturation uniquement |
| P2 | E-commerce léger + livraison | Colissimo / Chronopost labels (v2 API) |

### USA

| Priority | Vertical | Why |
|---|---|---|
| P0 | Home services (HVAC, plumbing) | Estimates, work orders, QuickBooks export |
| P0 | Real estate (residential) | Listings, showings, commission splits |
| P1 | E-commerce + fulfillment | Shopify sync (core connector later) |
| P1 | Field service | Dispatch, technician, parts |
| P2 | Nonprofit / grants | Pledges, restricted funds (light) |

### Africa (multi-country)

| Priority | Vertical | Why |
|---|---|---|
| P0 | Delivery + COD | Last mile, cash reconciliation |
| P0 | Retail / POS | Multi-currency, mobile money log |
| P1 | Schools / training | Enrollment, fee installments |
| P1 | Import / distribution | Stock, agents, territory |
| P2 | Agriculture co-op | Harvest lots, buyer contracts |

---

## Universal module families (all regions)

Rename Qatar-specific slugs to **neutral** names for international packs.

### 1. Real estate & property

| Slug | Name | Functions |
|---|---|---|
| `property-listings` | Property listings | Sale/rent listings, media, zones, status pipeline |
| `property-viewings` | Viewings | Calendar visits, confirmations, feedback |
| `lease-management` | Lease management | Deposits, rent schedule, renewals, receipts |
| `broker-commissions` | Broker commissions | Multi-agent splits, deal → closing |
| `property-maintenance` | Maintenance | Tickets, vendor assign, bill-back to tenant |

**Regional presets:** zones (postal codes, arrondissements, states), currency, legal clause templates EN/FR/AR.

### 2. E-commerce & retail (light)

| Slug | Name | Functions |
|---|---|---|
| `catalog-lite` | Product catalog | SKU, price, stock, variants |
| `orders-inbox` | Orders inbox | Manual/CSV orders, fulfillment status |
| `promo-codes` | Promo codes | % or fixed, campaign linkage |
| `returns-rma` | Returns | RMA workflow, credit notes |

### 3. Delivery & logistics

| Slug | Name | Functions |
|---|---|---|
| `delivery-dispatch` | Delivery dispatch | Routes, courier assign, proof of delivery |
| `cod-reconciliation` | COD reconciliation | Cash collected vs remitted |
| `warehouse-pickpack` | Pick & pack | Pick lists, bins, packing slips |

### 4. POS (point of sale)

| Slug | Name | Functions |
|---|---|---|
| `pos-register` | POS register | Sessions, sales, cash/card |
| `pos-multi-branch` | Multi-branch | Store-level totals |
| `pos-kitchen` | Kitchen display | F&B prep queue |
| `pos-z-report` | Daily Z report | Day close, tax summary export |

### 5. SaaS & subscriptions

| Slug | Name | Functions |
|---|---|---|
| `saas-subscriptions` | Subscriptions | Plans, MRR, trials |
| `usage-metering` | Usage metering | Metered billing events |
| `customer-onboarding` | Onboarding | Checklists, CSM notes |

### 6. Professional services

| Slug | Name | Functions |
|---|---|---|
| `agency-retainer` | Agency retainer | Monthly hours, overage |
| `consulting-mandates` | Consulting mandates | Deliverables, milestones |
| `events-brief` | Events | Budget, vendors, timeline |
| `fitout-contractor` | Fit-out / contractor | Progress billing, retainage |

### 7. Appointments (non-EMR)

| Slug | Name | Functions |
|---|---|---|
| `appointments` | Appointments | Practitioner slots, reminders |
| `service-packages` | Service packages | Multi-session packs |

> **Disclaimer:** No medical records (HIPAA/PHI) in zip modules — scheduling + billing only.

### 8. Cross-cutting helpers

| Slug | Name | Regions |
|---|---|---|
| `vat-tax-helper` | VAT / sales tax helper | EU, GCC, Canada HST |
| `document-expiry` | Document expiry alerts | Licenses, insurance, visas |
| `whatsapp-log` | WhatsApp log | Manual WA.me links + notes |
| `bilingual-contracts` | Bilingual contracts | CA, MA, GCC |
| `mobile-money-log` | Mobile money log | Africa — Orange Money, M-Pesa notes |
| `contractor-1099` | Contractor tracker | USA — W-9 / 1099 prep (admin only) |

---

## Starter packs (commercial bundles)

| Pack | Modules | Target |
|---|---|---|
| **Agency OS** | agency-retainer + consulting-mandates | FR / CA / US agencies |
| **Trades Pro** | consulting-mandates + property-maintenance + invoices core | CA / US home services |
| **Retail POS** | catalog-lite + pos-register + pos-z-report | Any retail |
| **Q-Commerce** | catalog-lite + orders-inbox + delivery-dispatch | Africa / GCC / EU |
| **Property Pro** | property-listings + viewings + lease + commissions | Real estate globally |
| **SaaS Starter** | saas-subscriptions + onboarding + usage-metering | Tech startups |
| **School Fees** | appointments + service-packages + installments | Africa / MENA |

---

## Module × FlowDesk core

| Module family | Clients | Projects | Quotes | Invoices | Providers | Portal | Forms |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Property | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| E-commerce | ✅ | ✅ | — | ✅ | — | ✅ | ✅ |
| Delivery | ✅ | ✅ | — | ✅ | ✅ | ✅ | — |
| POS | ✅ | — | — | ✅ | — | — | — |
| SaaS | ✅ | ✅ | ✅ | ✅ | — | ✅ | — |

---

## Nova & AI (how modules relate to the assistant)

Nova is **not limited to installed modules**. She can:

| Mode | What Nova does |
|---|---|
| **Workspace chat** | Answers from your live data (clients, revenue, invoices…) **and** general business / CRM / industry advice |
| **Voice navigation** | Opens pages by voice (free) |
| **Voice + AI forms** | Say *« nouvelle facture »* → create invoice → **AI voice dictation starts** → speak line items brief; say *« devise euro »* / *« client Acme »* to set fields → *« générer »* runs AI |

Installed modules appear in the sidebar when uploaded; Nova can still discuss **vertical ideas** (POS, leases, delivery) even if the zip is not installed yet.

---

## Roadmap: zip → core

| Need | Core extension |
|---|---|
| Stripe / local gateways | Payment webhooks per country |
| Shopify / WooCommerce | Order sync connector |
| E-invoicing (FR, etc.) | Government XML/API format |
| GPS / real-time dispatch | API + websocket |
| Module marketplace | Certified download store |

---

## Suggested build order (international)

| Phase | Focus | Regions |
|---|---|---|
| Q1 | property-listings + agency-retainer + vat-tax-helper | FR, CA, US |
| Q2 | catalog-lite + orders-inbox + delivery-dispatch | Africa, GCC |
| Q3 | pos-register + cod-reconciliation | Retail globally |
| Q4 | lease-management + appointments | Property + services |

---

## Example neutral `module.json`

```json
{
    "slug": "property-listings",
    "name": "Property Listings",
    "version": "1.0.0",
    "description": "Sale and rental listings linked to FlowDesk clients, projects, and invoices.",
    "author": "FlowDesk",
    "nav": { "icon": "projects" },
    "locales": ["en", "fr", "ar"]
}
```

---

*See also: [`MODULE.md`](MODULE.md) (technical how-to), [`modules-qatar.md`](modules-qatar.md) (GCC deep dive), [`NOVA.md`](NOVA.md) (assistant & voice).*
