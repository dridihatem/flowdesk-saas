# Qatar module plugins (FlowDesk)

Installable `.zip` extensions for the Qatar / GCC catalog ([`modules-qatar.md`](../../../modules-qatar.md)).

## Build

```bash
cd storage/stubs/qatar
chmod +x build-zips.sh
./build-zips.sh
```

Zips are written to `storage/stubs/qatar/zips/`. Upload in **Settings → Modules**.

## Implemented modules (v1)

| Slug | Pack | CRM integrations | Nova AI |
|---|---|---|---|
| **`qatar-real-estate`** | **Real Estate Suite (all-in-one)** | clients, projects, invoices, calendar, providers, forms, proposals | ✅ |
| `qatar-property-listings` | Real Estate Pro (standalone) | clients, projects, invoices, calendar, providers, forms, proposals | ✅ summary, client_email, project_description |
| `qatar-property-viewings` | Real Estate Pro | clients, calendar, forms | ✅ client_email, summary |
| `qatar-broker-commissions` | Real Estate Pro | clients, providers, invoices, projects, proposals | ✅ summary |
| `qatar-vat-helper` | Transversal | invoices, payments | ❌ calculator only |
| `qatar-catalog-lite` | Q-Commerce / Retail POS | clients, invoices, projects, forms | ✅ summary, project_description |
| `qatar-orders-inbox` | Q-Commerce | clients, invoices, payments, projects | ✅ summary, client_email |
| `qatar-delivery-dispatch` | Q-Commerce | clients, projects, payments | ✅ summary |
| `qatar-cod-reconciliation` | Q-Commerce | payments, invoices | ❌ |
| `qatar-appointments` | Clinic / salon | clients, invoices, calendar | ✅ client_email, summary |

## Bundles vs standalone

- **Recommended:** `qatar-real-estate.zip` — listings + viewings + commissions in one install with tab navigation.
- **À la carte:** individual `qatar-property-*` zips still work; their `module.json` declares `part_of_bundle` pointing to the suite.

## Cross-module links

Modules link to each other via `route('modules.show', 'slug')` and to core CRM via `route('clients.*')`, `route('projects.*')`, `route('invoices.*')`, `route('calendar.index')`, `route('providers.*')`.

## v1 limitations

- Mutations use **GET** `module_action` on the module page (no dedicated POST route yet).
- Nova calls `POST /assistant/suggest` with documented modes only.
- `integrations` / `ai` in `module.json` are documentary + settings badges.

## Next stubs (catalog only)

See §13 in `modules-qatar.md`: lease-management, pos-register, saas-subscriptions, events-brief, etc.
