# Créer un module FlowDesk

Guide pour construire un **module vertical installable** (fichier `.zip` via **Réglages → Modules**), relié au CRM existant (clients, projets, factures, calendrier…) et enrichi avec **Nova (IA)** si besoin.

**Exemple prêt à l'emploi :** `storage/stubs/module-sample/` · zip : `storage/stubs/quick-notes-module.zip`  
**Catalogues métier :** [`modules-qatar.md`](modules-qatar.md) · [`modules-international.md`](modules-international.md)

---

## Sommaire

| § | Sujet |
|---|---|
| [Démarrage rapide](#démarrage-rapide) | Créer un module en 5 étapes |
| [1. Structure](#1-structure-dun-module) | Arborescence du zip |
| [2. Manifeste](#2-le-manifeste-modulejson) | `module.json` + intégrations + IA |
| [3. Vues](#3-les-vues) | Pages Blade |
| [4. Migrations](#4-les-migrations) | Tables `module_*` |
| [5. Intégration CRM](#5-intégration-avec-le-crm-calendar-factures-paiements) | Lier calendar, invoice, payment… |
| [6. IA Nova](#6-ajouter-lia-nova-dans-un-module) | Bouton IA, dictée, crédits |
| [7. Exemple complet](#7-exemple-complet-property-listings) | Module immobilier de bout en bout |
| [8. Sécurité](#8-règles-de-validation-du-zip) | Limites et scan |
| [9. Installation](#9-créer-le-zip-et-installer) | Packager et déployer |
| [10. Cycle de vie](#10-cycle-de-vie) | Install / disable / uninstall |
| [11. Checklist](#11-checklist-avant-de-packager) | Avant publication |
| [12. Bundles](#12-modules-bundle-un-seul-zip) | Pack métier tout-en-un |

---

## Démarrage rapide

1. **Créer le dossier** `mon-module/` avec `module.json`, `views/index.blade.php`.
2. **Déclarer les intégrations** dans `module.json` → `integrations` (clients, invoices, calendar…).
3. **Ajouter une migration** `module_*` avec `company_id` + FK optionnelles (`client_id`, `invoice_id`…).
4. **Écrire la vue** : lire vos tables + liens `route()` vers le CRM + bouton Nova si besoin.
5. **Zipper et installer** : Réglages → Modules → Installer.

```
mon-module.zip
├── module.json          ← slug, name, integrations, ai
├── views/
│   └── index.blade.php  ← UI + liens CRM + Nova
└── database/migrations/
    └── …_create_module_….php
```

> Un module zip **étend** FlowDesk ; il ne remplace pas clients, factures ou calendrier. Vos données métier vivent dans `module_*` ; le lien vers le core se fait par **clés étrangères** et **liens navigation**.

---

## 1. Structure d'un module

```
mon-module/
├── module.json                  ← manifeste (obligatoire)
├── views/
│   ├── index.blade.php          ← page principale (obligatoire)
│   ├── show.blade.php           ← fiche détail (optionnel)
│   └── reports/
│       └── index.blade.php
├── database/
│   └── migrations/
│       └── 2026_01_01_000001_create_module_….php
├── lang/                        ← traductions UI (optionnel)
│   ├── en.json
│   ├── fr.json
│   ├── es.json
│   └── ar.json
└── assets/                      ← CSS, JS, images (optionnel)
    └── module.js
```

Le zip peut avoir les fichiers à la racine ou dans un seul dossier (`mon-module/module.json`) — les deux formats sont acceptés.

**Dossiers autorisés à la racine :** `views/`, `database/migrations/`, `lang/`, `assets/`, `module.json`.

---

## 2. Le manifeste `module.json`

### Champs de base

```json
{
    "slug": "mon-module",
    "name": "Mon Module",
    "version": "1.0.0",
    "description": "Description courte.",
    "author": "Votre nom",
    "nav": {
        "icon": "documents"
    }
}
```

| Champ | Obligatoire | Règles |
|---|---|---|
| `slug` | ✅ | Minuscules, chiffres, `-` ou `_`, 2–64 caractères. Unique par workspace. |
| `name` | ✅ | Nom sidebar + réglages (max 255). |
| `version` | ❌ | Défaut `1.0.0`. |
| `description` | ❌ | Liste des modules installés. |
| `author` | ❌ | Liste des modules installés. |
| `nav.icon` | ❌ | Défaut `modules`. Voir `resources/views/components/flow/nav-icon.blade.php`. |

### Intégrations CRM + IA (recommandé)

```json
{
    "slug": "property-listings",
    "name": "Property Listings",
    "version": "1.0.0",
    "description": "Biens immobiliers liés aux clients, projets et factures.",
    "author": "Votre nom",
    "nav": { "icon": "documents" },
    "integrations": {
        "clients": true,
        "projects": true,
        "invoices": true,
        "payments": true,
        "calendar": true,
        "proposals": false,
        "forms": true,
        "providers": false,
        "tickets": false
    },
    "requires": {
        "plan_features": ["modules"],
        "integrations": ["clients"]
    },
    "ai": {
        "modes": ["summary", "client_email", "project_description"],
        "label": "Nova résume un bien ou rédige un email client."
    }
}
```

| Champ | Rôle |
|---|---|
| `integrations` | **Quels modules core** le vertical utilise (doc + future UI Réglages). |
| `requires.integrations` | Dépendances fonctionnelles déclarées (ex. « nécessite Clients »). |
| `requires.plan_features` | Ex. `modules`, `ai_credits` si le module appelle Nova. |
| `ai.modes` | Modes `assistant.suggest` utilisés (voir §6). |
| `ai.label` | Texte d'aide affiché dans le module. |

### Packs de langue (optionnel)

**Nom / description localisés** dans le manifeste (sidebar, en-tête) :

```json
"locales": {
    "en": { "name": "Property Listings", "description": "Sale and rental listings…" },
    "fr": { "name": "Annonces immobilières", "description": "Biens à vendre ou à louer…" }
}
```

**Textes des vues** dans `lang/{locale}.json` (`en`, `fr`, `es`, `ar`) :

```json
{
    "add_listing": "Add listing",
    "save_listing": "Save listing"
}
```

Dans Blade : `{{ module_trans($module, 'add_listing') }}` (résolu en `module.{slug}.messages.add_listing`). Les clés CRM existantes (`__('Client')`, `__('Save')`) restent dans `lang/*.json` de la plateforme.

> `integrations`, `requires` et `ai` sont **documentaires** aujourd'hui. Le lien réel = **migrations + vues** (§5–§6).

---

## 3. Les vues

- `views/index.blade.php` → `/modules/{slug}` (**obligatoire**).
- `views/show.blade.php` → `/modules/{slug}/show`.
- `views/reports/index.blade.php` → `/modules/{slug}/reports`.

Rendu **dans le layout app** (sidebar, header, dark mode) — pas de `<x-app-layout>`, uniquement le contenu.

| Variable | Description |
|---|---|
| `$module` | `InstalledModule` (slug, name, version, `manifest`…) |
| `auth()->user()` | Utilisateur connecté |
| `auth()->user()->company` | Workspace courant |
| `auth()->user()->company_id` | **Toujours** filtrer vos requêtes DB |

Tailwind + Alpine.js sont disponibles (`x-data`, `dark:`).

---

## 4. Les migrations

- Exécutées **à l'installation**, rollback **à la désinstallation** (si aucun autre workspace n'a le même slug).
- Préfixe **`module_`** sur toutes les tables.
- Colonne **`company_id`** obligatoire (multi-tenant).
- `Schema::hasTable()` dans `up()` si la table existe déjà (autre workspace).

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_quick_notes')) {
            return;
        }

        Schema::create('module_quick_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_quick_notes');
    }
};
```

---

## 5. Intégration avec le CRM (calendar, factures, paiements…)

### Principe

```
┌─────────────────────────────────────────────────────────┐
│  Module vertical (zip)                                   │
│  tables module_*  +  vues /modules/{slug}                 │
└────────────┬────────────────────────────────────────────┘
             │ client_id, project_id, invoice_id (FK)
             │ liens route() vers pages core
             ▼
┌─────────────────────────────────────────────────────────┐
│  CRM FlowDesk (déjà installé dans le workspace)          │
│  clients · projects · proposals · invoices · payments    │
│  calendar · forms · providers · tickets                  │
└─────────────────────────────────────────────────────────┘
```

### Carte des intégrations

| Clé `integrations` | Table(s) core | Cas d'usage module |
|---|---|---|
| `clients` | `clients` | Acheteur, locataire, propriétaire |
| `projects` | `projects` | Mandat, chantier, dossier |
| `proposals` | `proposals` | Devis avant facture |
| `invoices` | `invoices`, `invoice_items` | Facturation, échéancier |
| `payments` | `payments` | Encaissements (souvent via facture) |
| `calendar` | `workspace_calendar_events` | Visites, RDV, rappels |
| `forms` | `forms`, `form_submissions` | Leads web |
| `providers` | `providers` | Agents, apporteurs |
| `tickets` | `support_tickets` | SAV |

### Étape A — FK dans la migration

```php
Schema::create('module_property_listings', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
    $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
    $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
    $table->foreignUlid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
    $table->string('title');
    $table->unsignedBigInteger('price_cents')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**Règles :**

- Toujours `where('company_id', $companyId)`.
- FK **nullable** + `nullOnDelete` sur le core.
- **Ne jamais** `ALTER` les tables core depuis un module.

### Étape B — Lire et afficher dans Blade

```blade
@php
    $companyId = auth()->user()->company_id;

    $listings = \Illuminate\Support\Facades\DB::table('module_property_listings')
        ->where('company_id', $companyId)
        ->latest('created_at')
        ->limit(20)
        ->get();

    $clientIds = $listings->pluck('client_id')->filter()->unique();
    $clients = $clientIds->isEmpty()
        ? collect()
        : \Illuminate\Support\Facades\DB::table('clients')
            ->where('company_id', $companyId)
            ->whereIn('id', $clientIds)
            ->get()
            ->keyBy('id');
@endphp

<ul class="space-y-2">
    @foreach ($listings as $row)
        <li class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
            <p class="font-semibold">{{ $row->title }}</p>
            @if ($row->client_id && isset($clients[$row->client_id]))
                <a href="{{ route('clients.edit', $row->client_id) }}"
                   class="text-sm text-indigo-600 hover:underline">
                    Client : {{ $clients[$row->client_id]->name }} →
                </a>
            @endif
            @if ($row->invoice_id)
                <a href="{{ route('invoices.show', $row->invoice_id) }}"
                   class="ml-3 text-sm text-indigo-600 hover:underline">
                    Facture →
                </a>
            @endif
        </li>
    @endforeach
</ul>
```

### Étape C — Actions CRM (liens, pas d'API module)

Les modules zip **n'ont pas de contrôleurs PHP**. Pour créer une facture, un RDV ou un paiement :

| Besoin | Action recommandée |
|---|---|
| Nouveau client | Lien `route('clients.create')` + stocker `client_id` après création manuelle |
| Nouvelle facture | Lien `route('invoices.create')` ou pré-remplir via brouillon `module_*` |
| Paiement | Page facture `invoices.show` (paiements enregistrés côté core) |
| RDV calendrier | Lien `route('calendar.index')` |
| Nouveau projet | Lien `route('projects.create')` |

### Routes core utiles

| Module core | Routes |
|---|---|
| Clients | `clients.index`, `clients.create`, `clients.edit` |
| Projets | `projects.show`, `projects.tasks.kanban` |
| Devis | `proposals.show`, `proposals.create` |
| Factures | `invoices.show`, `invoices.create`, `invoices.edit` |
| Paiements | Via `invoices.show` |
| Calendrier | `calendar.index` |
| Formulaires | `forms.show`, `forms.submissions.index` |

### Matrice modules métier × intégrations

| Vertical | `integrations` recommandées |
|---|---|
| Immobilier | `clients`, `projects`, `invoices`, `calendar` |
| POS / caisse | `clients`, `invoices`, `payments` |
| Livraison | `clients`, `projects`, `invoices` |
| Abonnements | `clients`, `invoices`, `payments` |
| Prise de RDV | `clients`, `calendar`, `forms` |
| SAV sectoriel | `clients`, `tickets`, `invoices` |

---

## 6. Ajouter l'IA (Nova) dans un module

Nova = assistant IA du workspace (`/assistant`). Un module peut appeler l'IA **sans PHP serveur** : Alpine.js + `POST /assistant/suggest`.

### Prérequis

- Plan avec **crédits IA** (`ai_credits`).
- Clé LLM plateforme (OpenAI, Anthropic ou Google).
- Chaque appel débite des crédits selon le `mode`.

### Modes disponibles (`POST /assistant/suggest`)

| Mode | Usage module |
|---|---|
| `summary` | Résumé fiche / rapport |
| `client_email` | Email au client du dossier |
| `proposal` | Texte commercial |
| `pricing` | Grille tarifaire |
| `project_description` | Description mission |
| `task_followup` | Relance |
| `ticket` | Réponse support |
| `seo` | Texte marketing |
| `growth_clients` | Conseils rétention (données workspace) |
| `growth_invoices` | Conseils recouvrement |
| `growth_projects` | Conseils pipeline |

> `landing_page` (page HTML + éditeur visuel) est **désactivé par défaut** (`FLOWDESK_LANDING_PAGE_WRITING_MODE=false`). Utilisez les autres modes.

Déclarez vos modes dans `module.json` → `ai.modes`.

### Bloc Nova copiable (vue module)

```blade
@php
    $aiBrief = collect([
        'Module' => $module->name,
        'Éléments' => $listings->count() ?? 0,
    ])->map(fn ($v, $k) => "$k : $v")->implode("\n");
@endphp

<div
    class="flow-panel mt-6 p-6"
    x-data="{
        context: @js($aiBrief),
        result: '',
        busy: false,
        error: '',
        async generate() {
            if (this.busy) return;
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(@js(route('assistant.suggest')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify({ mode: 'summary', context: this.context }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur IA');
                this.result = data.suggestion || '';
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },
    }"
>
    @if (! empty($module->manifest['ai']['label'] ?? null))
        <p class="text-xs text-slate-500">{{ $module->manifest['ai']['label'] }}</p>
    @endif

    <x-ai-voice-wrap target-id="module_nova_context" submit-button-id="module_nova_btn" class="mt-3">
        <textarea
            id="module_nova_context"
            x-model="context"
            rows="4"
            class="block w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900"
        ></textarea>
    </x-ai-voice-wrap>

    <button
        type="button"
        id="module_nova_btn"
        class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
        :disabled="busy"
        @click="generate()"
    >
        <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
        <span x-text="busy ? 'Nova…' : 'Générer avec Nova'"></span>
    </button>

    <p class="mt-2 text-[11px] text-slate-400">{{ __('AI-generated content — review before sending to clients.') }}</p>
    <p x-show="error" x-text="error" class="mt-2 text-sm text-rose-600" x-cloak></p>
    <pre x-show="result" x-text="result" class="mt-4 max-h-64 overflow-auto rounded-lg bg-slate-50 p-4 text-sm dark:bg-slate-900" x-cloak></pre>
</div>

<p class="mt-4">
    <a href="{{ route('assistant.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">
        Ouvrir Nova (chat complet) →
    </a>
</p>
```

### Enrichir le contexte IA avec le CRM

Injectez client, projet, facture liés dans `context` avant l'appel — Nova n'a pas accès automatique aux tables `module_*` :

```blade
@php
    $context = collect([
        'Bien' => $listing->title ?? null,
        'Prix' => isset($listing->price_cents) ? number_format($listing->price_cents / 100, 2).' €' : null,
        'Client' => $client->name ?? null,
        'Email client' => $client->email ?? null,
        'Notes' => $listing->notes ?? null,
    ])->filter()->map(fn ($v, $k) => "$k : $v")->implode("\n");
@endphp
```

### IA sur mesure (hors zip)

Mode dédié (ex. `property_listing_description`) = modification **core** :

1. `AiAssistantController::suggest` — valider le mode
2. `App\Services\AiAssistantPrompts` — prompt
3. `config/flowdesk.php` → `ai_task_credits` — coût

En zip : modes existants + contexte riche.

### JS dans `assets/`

Logique IA dans `assets/module-ai.js` ; fetch **uniquement** vers `route('assistant.suggest')`. Pas de `Http::` ni curl dans le PHP Blade.

---

## 7. Exemple complet : Property Listings

Structure cible :

```
property-listings/
├── module.json
├── views/
│   ├── index.blade.php      ← liste + liens CRM + Nova
│   └── show.blade.php       ← fiche bien + client / facture / calendrier
└── database/migrations/
    └── 2026_06_10_000001_create_module_property_listings_table.php
```

**`module.json`** — voir §2 (integrations : clients, projects, invoices, calendar ; ai : summary, client_email).

**Migration** — voir §5 étape A.

**`views/index.blade.php`** — combine :

1. Liste `module_property_listings` filtrée par `company_id`
2. Jointure lecture seule `clients` / `invoices`
3. Boutons : « Nouveau client », « Créer facture », « Calendrier »
4. Bloc Nova §6

**`views/show.blade.php`** — fiche d'un bien (`?id=` ou segment URL `/modules/property-listings/show` avec query) :

- Détail du bien
- Liens vers client / projet / facture liés
- Nova en mode `client_email` pour rédiger un email de visite

### Workflow utilisateur type

1. Installer le zip → sidebar « Property Listings »
2. Créer un bien dans le module (formulaire Alpine + `DB::table()->insert` dans Blade, ou saisie manuelle SQL seed)
3. Lier un **client** existant (`client_id`)
4. Ouvrir la **facture** core ou le **calendrier** via liens
5. **Nova** génère un email ou résumé à partir du contexte du bien

---

## 8. Règles de validation du zip

| Règle | Détail |
|---|---|
| Taille zip | Max **15 Mo** |
| Décompressé | Max **50 Mo** |
| Fichiers | Max **250**, **5 Mo** chacun |
| Obligatoire | `module.json` + `views/index.blade.php` |
| Dossiers | `views/`, `database/migrations/`, `assets/` uniquement |
| PHP | Uniquement `views/**/*.blade.php` et `database/migrations/*.php` |
| Interdit | `eval`, `exec`, `DB::raw`, `Http::`, `Mail::`, `Storage::`, `$_GET`… |
| Migrations | Pas de `DROP`/`ALTER` hors tables `module_*` |

Config : `config/modules.php` · scan : `ModuleSecurityScanner.php`.

**Exécution :** vues via `view()->file()` ; stockage `storage/app/workspaces/{company}/modules/` ; rôles `company_admin` / `team_member`.

---

## 9. Créer le zip et installer

```bash
cd property-listings
zip -r ../property-listings.zip module.json views database assets
```

**Réglages → Modules → Installer un module** (réservé `company_admin`, plan `modules`).

---

## 10. Cycle de vie

| Action | Effet |
|---|---|
| **Installation** | Extraction, migrations, sidebar |
| **Désactivation** | Masqué sidebar, fichiers conservés |
| **Réactivation** | Réapparaît dans la sidebar |
| **Désinstallation** | Rollback migrations (si seul workspace), suppression fichiers |

- Visible **uniquement** par le workspace installateur.
- `company_admin` + `team_member` : lecture ; install/désinstall : **admin seulement**.

---

---

## 12. Modules bundle (un seul zip)

Pour un **pack métier complet** (ex. immobilier = annonces + visites + commissions), préférez **un seul slug** plutôt que trois installs séparées.

### Structure

```
qatar-real-estate/
├── module.json          ← type: "bundle", pages, includes_modules
├── views/
│   ├── index.blade.php       ← hub / overview
│   ├── listings/index.blade.php
│   ├── viewings/index.blade.php
│   └── commissions/index.blade.php
├── database/migrations/      ← toutes les tables du pack
└── lang/
```

### `module.json` bundle

```json
{
    "slug": "qatar-real-estate",
    "type": "bundle",
    "pages": [
        { "slug": "", "label_key": "nav_overview" },
        { "slug": "listings", "label_key": "nav_listings" }
    ],
    "includes_modules": [
        { "slug": "qatar-property-listings", "name": "Property Listings", "required": true, "paid": false, "standalone_zip": true }
    ],
    "related_modules": [
        { "slug": "qatar-vat-helper", "name": "VAT Helper", "required": false, "paid": true, "price_hint": "$29/mo" }
    ]
}
```

| Champ | Rôle |
|---|---|
| `type: "bundle"` | Badge « Bundle » dans Réglages → Modules |
| `pages` | Onglets de navigation dans le module installé |
| `includes_modules` | Contenu **inclus** dans ce zip (affiché sur la page d’accueil du module) |
| `related_modules` | Add-ons **optionnels** ou payants (requis ou non, `price_hint`) |
| `part_of_bundle` | Sur un module **standalone** : indique le pack complet recommandé |

Exemple prêt : `storage/stubs/qatar/qatar-real-estate/` → `zips/qatar-real-estate.zip`.

---

## 11. Checklist avant de packager

### Structure

- [ ] `module.json` : `slug`, `name` valides
- [ ] `views/index.blade.php` présent
- [ ] Migrations avec `down()` + préfixe `module_` + `company_id`

### Intégrations CRM

- [ ] `integrations` renseigné dans `module.json`
- [ ] FK `client_id` / `project_id` / `invoice_id` si métier lié
- [ ] Toutes requêtes filtrées par `company_id`
- [ ] Liens `route()` testés (client, facture, calendrier, projet)
- [ ] Message UI si prérequis manquant (ex. aucun client)

### IA Nova

- [ ] `ai.modes` + `ai.label` dans `module.json`
- [ ] Contexte IA = données module + entités core liées
- [ ] Crédits IA / erreur API gérés côté UI
- [ ] Avertissement « contenu IA — à relire »

### Sécurité & tests

- [ ] Pas de PHP hors `views/` et `migrations/`
- [ ] Zip < 15 Mo
- [ ] Parcours : install → usage → lien CRM → Nova → désinstall

---

## Référence rapide

| Besoin | Section |
|---|---|
| Relier un client / facture / calendrier | §5 |
| Colonnes FK en migration | §5 étape A |
| Bouton « Générer avec Nova » | §6 |
| Dictée vocale dans le module | §6 (`x-ai-voice-wrap`) |
| Module immobilier complet | §7 |
| Idées sectorielles Qatar / international | `modules-qatar.md`, `modules-international.md` |
