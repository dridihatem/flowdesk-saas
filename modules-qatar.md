# FlowDesk — Catalogue de modules pour le Qatar

> Idées de modules installables (`.zip` via **Réglages → Modules**) pour adapter FlowDesk aux startups et PME qatariennes : **immobilier, e-commerce, livraison, POS, services, etc.**  
> Voir [`MODULE.md`](MODULE.md) pour la structure technique (Blade + migrations, `company_id`, préfixe `module_`).  
> Pour **Canada, France, USA, Afrique** et autres marchés, voir [`modules-international.md`](modules-international.md).

---

## Comment un module s’intègre au cœur FlowDesk

Chaque module vertical **ne remplace pas** le CRM — il **s’étend** :

| Entité FlowDesk existante | Rôle pour les modules Qatar |
|---|---|
| **Clients** | Acheteur, locataire, propriétaire, marchand, livreur |
| **Projets** | Mandat immo, chantier, campagne e-com, tournée livraison |
| **Devis / factures** | Offre de vente/location, commande, ticket POS, abonnement SaaS |
| **Apporteurs (providers)** | Agents immo, affiliés, influenceurs, partenaires livraison |
| **Formulaires** | Lead site, demande de visite, inscription marchand |
| **Portail client** | Suivi commande, bail, paiement échéancier |
| **Paiements** | QAR, Stripe, passerelles GCC (à intégrer au core) |

```
Module vertical (zip)
    ↔ tables module_* (biens, stocks, tournées…)
    ↔ vues /modules/{slug}
    ↔ liens manuels ou futurs webhooks vers clients / projets / factures
```

**Limite actuelle des modules zip :** pas de contrôleurs PHP — logique dans Blade + requêtes DB. Pour POS temps réel, tracking GPS, sync marketplace : prévoir **module v1 (CRUD + rapports)** puis **extension core** (API, jobs) si le vertical décolle.

---

## Priorisation recommandée (Qatar / GCC)

| Priorité | Secteur | Pourquoi Qatar |
|---|---|---|
| 🔴 P0 | Immobilier & brokers | Économie relationnelle, commissions, expats, Vision 2030 construction |
| 🔴 P0 | Services B2B / agences | Déjà le cœur FlowDesk — packs sectoriels |
| 🟠 P1 | E-commerce léger + livraison | Croissance retail, livraison Doha, Q-commerce |
| 🟠 P1 | POS retail / F&B | Souqs, malls, cloud kitchen |
| 🟡 P2 | Location véhicules / équipement | Tourisme, événements, chantiers |
| 🟡 P2 | Clinique / salon / spa | Services rendez-vous + facturation |
| 🟢 P3 | Éducation / formation | Cours, inscriptions, paiements échelonnés |
| 🟢 P3 | Événementiel | Stands, sponsors, billetterie légère |

---

## 1. Immobilier & propriété (Real Estate)

### 1.1 `qatar-property-listings` — Annonces & portefeuille

| Champ | Valeur |
|---|---|
| **Nom** | Property Listings |
| **Cible** | Agences immo, promoteurs, brokers indépendants |
| **Fonctions** | Fiches bien (vente/location), photos, prix QAR, zone (The Pearl, Lusail, West Bay…), statut (disponible / réservé / vendu), lien client propriétaire |
| **Tables** | `module_property_listings`, `module_property_media`, `module_property_zones` |
| **Lien FlowDesk** | 1 bien → 1 **projet** ; lead formulaire → **inquiry** ; vente → **facture** commission |
| **Spécifique Qatar** | Zones Doha prédéfinies, AR/EN, superficie m² + sqft, « furnished / semi / unfurnished », réf. permis si besoin |

### 1.2 `qatar-property-viewings` — Visites & open house

| Fonctions | Calendrier visites, confirmation client, no-show, feedback post-visite |
| **Lien** | **Calendar** workspace, **portail client**, rappels e-mail |
| **Tables** | `module_property_viewings` |

### 1.3 `qatar-lease-management` — Baux & loyers

| Fonctions | Contrat bail, dépôt, échéances loyer, indexation, renouvellement, quittance |
| **Lien** | **Project installments** existant, **factures** récurrentes |
| **Tables** | `module_leases`, `module_lease_payments` |
| **Qatar** | Durée bail typique, clauses EN/AR, rappels WhatsApp (lien manuel v1) |

### 1.4 `qatar-broker-commissions` — Commissions agents immo

| Fonctions | Split commission multi-agents, % par rôle, pipeline deal → closing |
| **Lien** | **Providers** + négociations déjà en place — module = UI métier immo |
| **Tables** | `module_deal_splits` (lien `negotiation_id`, `provider_id`) |

### 1.5 `qatar-offplan-sales` — Vente sur plan

| Fonctions | Projet promoteur, phases, % paiement construction, échéancier off-plan |
| **Lien** | **Projets** par tour/phase, **installments**, portail investisseur |
| **Tables** | `module_offplan_projects`, `module_offplan_milestones` |

### 1.6 `qatar-property-maintenance` — Facility / maintenance locative

| Fonctions | Tickets maintenance, prestataire assigné, SLA, coût refacturé locataire |
| **Lien** | **Support tickets**, **project tasks**, **facture** extra |
| **Tables** | `module_maintenance_requests` |

---

## 2. E-commerce & retail (sans remplacer Shopify)

Objectif : **PME qui vendent en ligne + ont besoin CRM/facturation/portail** — pas un Shopify complet en zip.

### 2.1 `qatar-catalog-lite` — Catalogue produits

| Fonctions | SKU, prix QAR, stock simple, catégories, images, variantes (taille/couleur) |
| **Tables** | `module_products`, `module_product_variants`, `module_stock_movements` |
| **Lien** | Commande → **projet** ou **facture** ; client → **clients** |

### 2.2 `qatar-orders-inbox` — Commandes omnicanal

| Fonctions | Commande manuelle / import CSV, statuts (nouvelle → préparée → expédiée → livrée), paiement COD / en ligne |
| **Tables** | `module_orders`, `module_order_lines` |
| **Lien** | **Invoices**, **payments**, portail **suivi commande** |

### 2.3 `qatar-marketplace-sync` — Sync marketplaces (v1 manuel)

| Fonctions | Import commandes Snoonu / Rafeeq / Instagram DM / WhatsApp (saisie structurée), pas d’API lourde en v1 |
| **Tables** | `module_channel_orders` (`channel`: snoonu, instagram, website…) |
| **Roadmap core** | API webhooks `order.created` |

### 2.4 `qatar-promo-codes` — Codes promo & campagnes

| Fonctions | % ou montant fixe, validité, usage max, lien campagne email marketing |
| **Tables** | `module_promo_codes`, `module_promo_redemptions` |
| **Lien** | **Email marketing**, **proposals** (remise ligne) |

### 2.5 `qatar-returns-rma` — Retours & SAV

| Fonctions | Demande retour, motif, remboursement ou échange, statut |
| **Tables** | `module_returns` |
| **Lien** | **Facture** avoir, **tickets** |

---

## 3. Livraison & logistique (Last mile)

### 3.1 `qatar-delivery-dispatch` — Tournées & livreurs

| Fonctions | Créneaux livraison, assignation livreur, statut (assigné → en route → livré), preuve photo/signature |
| **Tables** | `module_deliveries`, `module_delivery_stops`, `module_couriers` |
| **Lien** | **Orders** module, **clients**, SMS/e-mail (core) |
| **Qatar** | Zones Doha, frais par zone QAR, créneau Ramadan |

### 3.2 `qatar-fleet-lite` — Flotte véhicules

| Fonctions | Véhicule, assurance, entretien, km, affectation livreur |
| **Tables** | `module_vehicles`, `module_vehicle_maintenance` |

### 3.3 `qatar-warehouse-pickpack` — Préparation commandes

| Fonctions | Liste picking, emplacement rack, colis, poids, étiquette (PDF simple) |
| **Tables** | `module_pick_lists`, `module_bins` |
| **Lien** | **Catalog lite**, **orders** |

### 3.4 `qatar-cod-reconciliation` — Rapprochement cash on delivery

| Fonctions | COD collecté par livreur, remise caisse, écart, validation admin |
| **Tables** | `module_cod_collections` |
| **Lien** | **Payments** manuels, **rapports** |

---

## 4. POS (Point of sale)

> POS temps réel = v2 avec API core. **v1 zip** = caisse enregistreuse légère + clôture journalière.

### 4.1 `qatar-pos-register` — Caisse & tickets

| Fonctions | Panier rapide, scan SKU (saisie), modes paiement (cash, card, wallet), ticket thermique (PDF/HTML), session caisse ouverture/fermeture |
| **Tables** | `module_pos_sessions`, `module_pos_sales`, `module_pos_sale_lines` |
| **Lien** | **Clients** walk-in optionnel, **facture** auto, **stock** catalog |

### 4.2 `qatar-pos-multi-branch` — Multi-succursale

| Fonctions | Magasin / mall branch, caissier, totaux par point de vente |
| **Tables** | `module_branches`, `module_branch_users` |
| **Qatar** | Souq Waqif, malls (City Center, Villaggio…) en preset |

### 4.3 `qatar-pos-kitchen` — Kitchen display (F&B)

| Fonctions | Commande → file cuisine, statut préparation, numéro ticket |
| **Tables** | `module_kitchen_tickets` |
| **Lien** | **POS register** ou **orders** |

### 4.4 `qatar-pos-z-report` — Rapports Z / TVA jour

| Fonctions | Total jour QAR, TVA 5 %, export comptable |
| **Tables** | `module_pos_daily_closures` |
| **Qatar** | Format export pour comptable local |

---

## 5. SaaS & abonnements (pour startups tech Qatar)

### 5.1 `qatar-saas-subscriptions` — Abonnements clients B2B

| Fonctions | Plans, MRR, renouvellement, upgrade/downgrade, essai |
| **Tables** | `module_saas_plans`, `module_saas_subscriptions` |
| **Lien** | **Clients**, **factures** récurrentes (manuelles ou cron core) |

### 5.2 `qatar-usage-metering` — Facturation à l’usage

| Fonctions | Compteurs (API calls, sièges, GB), paliers, export facturation |
| **Tables** | `module_usage_events`, `module_usage_quotas` |
| **Lien** | **Invoices** ligne variable, **AI credits** (même logique) |

### 5.3 `qatar-customer-onboarding` — Onboarding client SaaS

| Fonctions | Checklist activation, étapes, % complétion, CSM notes |
| **Tables** | `module_onboarding_checklists`, `module_onboarding_progress` |
| **Lien** | **Projects** = onboarding client |

### 5.4 `qatar-api-keys-portal` — Clés API client (affichage)

| Fonctions | Vue clés API entreprise (lecture seule depuis `companies`), doc liens, quotas |
| **Note** | Données dans core ; module = UI + guide intégration |

---

## 6. Services professionnels (packs Qatar)

Modules « template » qui configurent formulaires + tableaux de bord métier.

### 6.1 `qatar-agency-retainer` — Agence digitale (forfait)

| Fonctions | Forfait mensuel heures, consommation, dépassement facturable |
| **Tables** | `module_retainers`, `module_retainer_usage` |
| **Lien** | **Project tasks** + time tracking existant |

### 6.2 `qatar-consulting-mandates` — Cabinets conseil

| Fonctions | Mandat, livrables, jalons, TJM |
| **Lien** | **Projects**, **proposals**, **Gantt** |

### 6.3 `qatar-events-brief` — Événementiel

| Fonctions | Brief événement, budget, fournisseurs, timeline, devis global |
| **Tables** | `module_events`, `module_event_vendors` |
| **Qatar** | Stades, centres congrès, saison haute |

### 6.4 `qatar-fitout-contractor` — Fit-out / construction légère

| Fonctions | Devis chantier, % avancement, acomptes, réserves |
| **Lien** | **Installments**, **project files vault** |

---

## 7. Santé, beauté, bien-être (réglementé — v1 admin seulement)

### 7.1 `qatar-appointments` — Rendez-vous

| Fonctions | Créneaux praticien, réservation, rappel, no-show fee |
| **Tables** | `module_appointments`, `module_practitioners` |
| **Lien** | **Calendar**, **clients**, **facture** consultation |

### 7.2 `qatar-clinic-packages` — Forfaits séances

| Fonctions | Pack 10 séances, solde restant, expiration |
| **Tables** | `module_service_packages` |

> **Disclaimer** : pas de dossier médical HIPAA/PHI en zip — rester sur **planning + facturation**, pas EMR.

---

## 8. Transport & location

### 8.1 `qatar-car-rental` — Location véhicules

| Fonctions | Parc auto, réservation, caution, km, état départ/retour photos |
| **Tables** | `module_vehicles_rental`, `module_rental_bookings` |
| **Lien** | **Clients**, **contrat** PDF, **facture** |

### 8.2 `qatar-chauffeur-trips` — Chauffeur / transfert aéroport

| Fonctions | Course, Hamad airport preset, tarif zone, chauffeur assigné |
| **Tables** | `module_trips` |

---

## 9. Modules transversaux Qatar (tous secteurs)

| Slug | Nom | Utilité |
|---|---|---|
| `qatar-vat-helper` | VAT 5 % helper | Calcul TVA, mention légale facture, rapport trimestriel |
| `qatar-hijri-dates` | Hijri calendar | Affichage date hijri sur contrats / baux (optionnel) |
| `qatar-whatsapp-log` | WhatsApp log | Journal communications client (lien WA.me, note, pas d’API Meta v1) |
| `qatar-document-expiry` | Expiry alerts | Alertes CR, visa employé, assurance, licence commerciale |
| `qatar-qdb-export` | Business metrics | Export KPI pour dossiers financement / investissement |
| `qatar-ndpr-checklist` | Privacy checklist | Checklist conformité données personnelles Qatar |
| `qatar-bilingual-contracts` | Bilingual docs | Modèles contrat EN/AR avec champs fusion |
| `qatar-sponsor-management` | Sponsorship | Suivi sponsors événement / sport (Qatar 2030 events) |

---

## 10. Packs « startup Qatar » prêts à installer

Proposition commerciale : **un zip = un pack** qui documente quels modules installer ensemble.

| Pack | Modules inclus | Startup type |
|---|---|---|
| **Real Estate Pro** | listings + viewings + broker-commissions + lease | Agence immo Doha |
| **Q-Commerce** | catalog-lite + orders + delivery-dispatch + cod-reconciliation | Boutique en ligne + livraison |
| **Retail POS** | catalog-lite + pos-register + pos-z-report + promo-codes | Boutique mall / souq |
| **Cloud Kitchen** | catalog + orders + kitchen + delivery | F&B livraison |
| **Agency OS** | agency-retainer + consulting-mandates (+ core FlowDesk) | Agence marketing |
| **SaaS Starter** | saas-subscriptions + onboarding + usage-metering | Startup tech QSTP |
| **Facility** | property-maintenance + appointments + lease | Gestion locative |
| **Events Qatar** | events-brief + sponsor-management + invoicing core | Organisateur événements |

---

## 11. Matrice : module × fonctionnalité FlowDesk core

| Module | Clients | Projets | Devis | Factures | Providers | Portail | Forms |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Property listings | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Lease management | ✅ | ✅ | — | ✅ | — | ✅ | — |
| Catalog + orders | ✅ | ✅ | — | ✅ | — | ✅ | ✅ |
| Delivery dispatch | ✅ | ✅ | — | ✅ | ✅ | — | — |
| POS register | ✅ | — | — | ✅ | — | — | — |
| SaaS subscriptions | ✅ | ✅ | ✅ | ✅ | — | ✅ | — |

---

## 12. Roadmap technique (au-delà du zip)

Quand un vertical Qatar génère du revenu, migrer vers le **core** :

| Besoin | Extension core |
|---|---|
| Paiement QAR / Skip Cash / Tap | `InvoicePayment` + webhooks |
| GPS livreur temps réel | API + Pusher + app mobile |
| POS offline | PWA + sync API |
| Sync Shopify / WooCommerce | Connecteur + webhooks |
| E-invoicing Qatar FTA | Format facture + API gouvernement |
| Marketplace modules public | Store FlowDesk (téléchargement modules certifiés) |

---

## 13. Ordre de développement suggéré (12 mois)

| Mois | Module | Secteur |
|---|---|---|
| M1–M2 | `qatar-property-listings` + `qatar-broker-commissions` | Immo |
| M3 | `qatar-property-viewings` + `qatar-vat-helper` | Immo + transversal |
| M4–M5 | `qatar-catalog-lite` + `qatar-orders-inbox` | E-commerce |
| M6 | `qatar-delivery-dispatch` + `qatar-cod-reconciliation` | Livraison |
| M7–M8 | `qatar-pos-register` + `qatar-pos-z-report` | Retail |
| M9 | `qatar-lease-management` | Immo / facility |
| M10 | `qatar-saas-subscriptions` | Startups tech |
| M11–M12 | Packs zip + marketplace interne + 3 pilotes payants par pack |

---

## 14. Exemple `module.json` — Real Estate Listings

```json
{
    "slug": "qatar-property-listings",
    "name": "Property Listings (Qatar)",
    "version": "1.0.0",
    "description": "Manage sale and rental listings in QAR with Doha zones, linked to FlowDesk clients and projects.",
    "author": "FlowDesk",
    "nav": { "icon": "projects" },
    "integrations": {
        "clients": true,
        "projects": true,
        "invoices": true,
        "calendar": true,
        "providers": true,
        "forms": true
    },
    "ai": {
        "modes": ["summary", "client_email", "project_description"],
        "label": "Nova drafts listing summaries and client viewing emails."
    }
}
```

---

## 15. Modules implémentés (stubs zip v1)

> Sources : `storage/stubs/qatar/` · zips prêts : `storage/stubs/qatar/zips/` · build : `./build-zips.sh`

| Slug | Pack commercial | Intégrations CRM | Nova |
|---|---|---|---|
| **`qatar-real-estate`** | **Real Estate Suite (bundle)** | clients, projects, invoices, calendar, providers, forms, proposals | ✅ |
| `qatar-property-listings` | Real Estate Pro (standalone) | clients, projects, invoices, calendar, providers, forms, proposals | ✅ summary, client_email, project_description |
| `qatar-property-viewings` | Real Estate Pro | clients, calendar, forms | ✅ client_email, summary |
| `qatar-broker-commissions` | Real Estate Pro | clients, providers, invoices, projects, proposals | ✅ summary |
| `qatar-vat-helper` | Transversal | invoices, payments | ❌ calculateur |
| `qatar-catalog-lite` | Q-Commerce / Retail POS | clients, invoices, projects, forms | ✅ summary, project_description |
| `qatar-orders-inbox` | Q-Commerce | clients, invoices, payments, projects | ✅ summary, client_email |
| `qatar-delivery-dispatch` | Q-Commerce | clients, projects, payments | ✅ summary |
| `qatar-cod-reconciliation` | Q-Commerce | payments, invoices | ❌ |
| `qatar-appointments` | Clinic / salon | clients, invoices, calendar | ✅ client_email, summary |

**Bundles recommandés :** installez `qatar-real-estate.zip` pour tout l’immobilier en une fois. Les zips `qatar-property-*` restent disponibles à la carte.

**Liens inter-modules :** les vues pointent vers `route('modules.show', ['slug' => $module->slug, 'page' => '…'])` dans un bundle, ou vers un autre slug installé.

**Catalogue restant (§1–§9) :** lease-management, pos-register, saas-subscriptions, events-brief, etc. — spec seulement, pas encore de zip.

---

## 16. Pitch investisseur (une phrase par vertical)

- **Immo** : « Le CRM des agences qatariennes avec commissions brokers et baux intégrés. »
- **E-commerce** : « Commandes + facturation + portail client sans quitter le workspace. »
- **Livraison** : « Dernière mile Doha avec COD et rapprochement caisse. »
- **POS** : « Caisse QAR, TVA 5 %, multi-succursale pour retail local. »
- **SaaS** : « Abonnements et onboarding pour startups hébergées au QSTP. »

---

*Complète [`idea.md`](idea.md) et [`pitch-deck.md`](pitch-deck.md) — section « 3 vertical templates for Qatar ».*
