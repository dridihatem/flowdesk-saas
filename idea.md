# FlowDesk — Idée produit & orientation Qatar (programme d’investissement)

> Document de synthèse pour pitch investisseur, incubateur ou programme d’accompagnement (Qatar / GCC).  
> **Produit actuel :** plateforme SaaS multi-tenant B2B — du premier lead à la facture payée.

---

## 1. En une phrase

**FlowDesk** est un **espace de travail tout-en-un** pour les entreprises de services (agences, cabinets, réseaux commerciaux, prestataires) : clients, projets, devis, factures, paiements, portail client, apporteurs d’affaires, formulaires web, marketing e-mail et IA — chaque entreprise sur **son propre sous-domaine** (`votre-entreprise.flowdesk.com`).

---

## 2. Le problème

Les PME et ETI de services jonglent entre **5 à 15 outils** qui ne se parlent pas :

| Besoin métier | Outil souvent utilisé | Conséquence |
|---|---|---|
| CRM & clients | Excel, HubSpot, Notion | Données éparpillées |
| Projets & livraison | Trello, Asana | Pas de lien avec la facturation |
| Devis & négociation | Word, PDF, e-mail | Pas de traçabilité |
| Facturation | QuickBooks, outil local | Devise, TVA, relances manuelles |
| Paiement en ligne | Stripe, passerelle locale | Intégration artisanale |
| Partenaires / commissions | Tableurs | Erreurs, litiges |
| Portail client | Aucun ou SharePoint | Support chargé, transparence faible |
| Leads web | Google Forms | Pas de qualification structurée |

**Résultat :** perte de temps, retards de paiement, visibilité faible sur le pipeline, difficulté à scaler au-delà de 10–50 collaborateurs.

---

## 3. La solution FlowDesk

Une **plateforme unique**, multi-locataire (SaaS), où chaque entreprise dispose de :

- **Son workspace isolé** (sous-domaine, branding, thème, langue)
- **Un parcours commercial complet** : lead → projet → devis → négociation → facture → paiement
- **Des portails dédiés** : client, apporteur d’affaires (business provider)
- **De l’automatisation** : relances factures, e-mails marketing, assistant IA, webhooks
- **Une architecture prête pour l’international** : 4 langues (EN, FR, ES, **AR**), multi-devises, rôles & permissions

### Parcours type (démo investisseur)

```
Widget / formulaire web
    → Lead (inquiry)
    → Projet + tâches (kanban, Gantt, commentaires client ↔ équipe)
    → Devis (proposal) + négociation
    → Facture + paiement (Stripe / passerelle locale)
    → Portail client (suivi, échéancier, paiement)
    → Commission apporteur d’affaires (si applicable)
```

---

## 4. Piliers produit (état actuel)

| Pilier | Contenu |
|---|---|
| **Multi-tenant** | Sous-domaines, stockage par tenant, API token par entreprise |
| **CRM léger** | Clients, projets, statuts, deadlines, coffre-fort fichiers |
| **Commercial** | Devis, lignes, validité, conversion facture, négociation |
| **Facturation** | Multi-devises (USD, EUR, GBP, TND), TVA, timbre, PDF, relances |
| **Paiements** | Stripe, PayPal, Flouci (Tunisie), enregistrement manuel |
| **Apporteurs** | Partenariat signé, commissions par projet, demandes de paiement |
| **Portails** | Client (projets, factures, commentaires tâches) ; Provider (commissions) |
| **Marketing** | Formulaires embarqués, e-mail marketing, séquences, tracking |
| **IA** | Assistant, génération devis/factures/campagnes (crédits par plan) |
| **Sécurité** | 2FA admin, IP allowlist, audit logs, RBAC (Spatie) |
| **Extensibilité** | Modules installables (.zip) par workspace |
| **Abonnement** | Plans Starter / Pro / Enterprise + limites + Stripe billing |

**Stack :** Laravel 13, MySQL, Tailwind, Alpine.js, queues, backups, Sentry.

---

## 5. Modèle économique

| Source de revenus | Description |
|---|---|
| **Abonnement SaaS** | Mensuel par plan (utilisateurs, projets, formulaires, crédits IA) |
| **Add-ons** | Support prioritaire, volume formulaires, etc. |
| **Usage IA** | Crédits au-delà du forfait |
| **Commission plateforme** (optionnel) | % sur transactions si marketplace providers |

**Cible clients :** agences digitales, cabinets conseil, BTP services, réseaux commerciaux, franchises de services, **SME du Qatar et du GCC** cherchant à digitaliser ventes + delivery + facturation.

---

## 6. Positionnement géographique actuel vs Qatar

| Aujourd’hui (code & GTM) | Orientation naturelle |
|---|---|
| Tunisie (Flouci, TND) | Marché pilote MENA |
| Français + arabe | **Aligné Qatar / GCC** |
| USD / EUR pricing | À adapter en **QAR** |
| Stripe global | À compléter par **passerelles GCC** |
| Hébergement générique | À préciser **résidence données Qatar/GCC** |

Le produit n’est **pas** un vertical unique (restaurant, clinique…) : c’est une **plateforme horizontale** avec des **packs sectoriels** possibles — idéal pour un hub régional type Qatar.

---

## 7. Pourquoi le Qatar ? (angle investissement)

Le Qatar poursuit **Vision 2030** : diversification hors hydrocarbures, **SME digitization**, hub régional, entrepreneuriat, tech et services.

FlowDesk peut se positionner comme :

1. **Infrastructure logicielle pour les PME qatariennes** — remplacer outils étrangers fragmentés par une solution **arabe + anglais**, hébergée régionalement.
2. **Outil d’export de services** — entreprises qatariennes qui facturent clients GCC / international (multi-devises, portail client).
3. **Plateforme pour réseaux commerciaux** — commissions apporteurs, contrats signés, traçabilité (secteurs immobilier, conseil, événementiel, B2B services).
4. **SaaS « made in Qatar »** — IP locale, emplois tech (Qatarization), partenariats avec QDB, QFC, QSTP, Invest Qatar.

**Programmes potentiellement visés :**

- **QDB** — financement / accompagnement startups & SME tech  
- **QFC** — structure holding tech, accès marché régional  
- **QSTP** — R&D, IA, intégrations  
- **MCIT / Digital Incubation** — programmes innovation numérique  
- **Invest Qatar** — attractivité investisseur étranger + ancrage local  

*(À valider selon le programme exact visé et le stade : idée, MVP, premiers clients.)*

---

## 8. Ce qu’il faut changer / adapter pour le Qatar

### 8.1 Produit & conformité (priorité haute)

| Changement | Détail | Effort estimé |
|---|---|---|
| **Devise QAR** | Ajouter `QAR` dans `config/flowdesk.php`, labels, plans, factures | Faible |
| **Pays Qatar** | `QA` → `QAR` dans `country_currency`, inscription entreprise | Faible |
| **Arabe (Qatar)** | Renforcer `ar` : terminologie locale, dates hijri (optionnel), RTL QA | Moyen |
| **Anglais** | Langue par défaut investisseur / B2B Qatar | Déjà en place |
| **Passerelles paiement GCC** | **Skip Cash**, **QPay**, **Tap Payments**, **PayFort/Amazon**, banques locales | Moyen–élevé |
| **Facturation légale** | Champs TVA Qatar (5 %), numéro commercial, format facture conforme | Moyen + expert-comptable local |
| **E-invoicing** | Suivre évolutions **FTA / e-invoicing** si obligation future | Roadmap |
| **Résidence données** | Hébergement **Qatar ou GCC** (AWS Bahrain, OCI, local DC) | Infra + légal |
| **Conformité** | Politique confidentialité, DPA, alignement **NDPR Qatar** / bonnes pratiques GCC | Juridique |

### 8.2 Go-to-market Qatar

| Changement | Détail |
|---|---|
| **Entité locale** | Création société Qatar (ou QFC) — souvent **exigence** des programmes |
| **Pricing en QAR** | Plans affichés en rials ; option USD pour clients internationaux |
| **Site marketing** | Page `/qa` ou domaine `.qa` : Vision 2030, secteurs cibles (construction services, events, consulting) |
| **Cas d’usage locaux** | 3 démos : agence digitale Doha, cabinet conseil, réseau apporteurs immobilier |
| **Partenariats** | Chambres de commerce, accélérateurs, intégrateurs IT locaux |
| **Support** | Horaires GST, WhatsApp Business, support **AR + EN** |
| **Qatarization** | Plan d’embauche locale (commercial, support, dev) — **critère fréquent des programmes** |

### 8.3 Technique & confiance entreprise

| Changement | Détail |
|---|---|
| **SSO entreprise** | SAML / Azure AD — demandé par mid-market+ |
| **SLA & support** | Tier Enterprise avec SLA documenté |
| **API publique v1** | Webhooks + REST documentés (Zapier, outils locaux) |
| **Rapports export** | Excel/PDF pour autorités ou partenaires |
| **Haute dispo** | Multi-AZ, monitoring, plan de reprise |

### 8.4 Narratif investisseur (à réécrire dans le deck)

| Avant (message actuel) | Après (message Qatar) |
|---|---|
| « Tunisia first, Europe FR » | « **GCC hub from Qatar** — Arabic-first SME operating system » |
| « Flouci + Stripe » | « **Regional payments** (QAR, GCC gateways) + Stripe international » |
| « Multi-sector global » | « **3 vertical templates** for Qatar: agencies, consulting, broker networks » |
| « Side project / MVP » | « **Team + entity + pilots** with measurable ARR in 12 months » |

---

## 9. Proposition de valeur pour un programme d’investissement

### Problème Qatar adressé
> 90 %+ des PME qatariennes utilisent encore e-mail + Excel pour gérer clients et factures. Les solutions globales (Salesforce, Zoho) sont chères, peu adaptées à l’arabe et sans portail client intégré ni gestion d’apporteurs — typiques des économies relationnelles du Golfe.

### Solution
> FlowDesk : **un workspace par entreprise**, en arabe et anglais, du lead au paiement, avec portails client et partenaire — **hébergé dans la région**.

### Traction à construire (honest roadmap 12 mois)
- **M0–M3 :** entité Qatar, QAR, paiements pilotes, 5 entreprises beta (gratuit)  
- **M4–M6 :** 20 clients payants, 1 partenaire intégrateur, ARR cible à définir  
- **M7–M12 :** expansion GCC (UAE, KSA), module conformité facture, SSO  

### Utilisation des fonds (exemple)
| Poste | % indicatif |
|---|---|
| Équipe locale (sales, support, dev) | 40 % |
| Produit (paiements GCC, conformité, infra) | 25 % |
| Marketing & partenariats Qatar | 20 % |
| Juridique, compliance, hébergement | 10 % |
| Fonds de roulement | 5 % |

### KPIs à suivre pour investisseurs
- MRR / ARR  
- Nombre de workspaces actifs (entreprises)  
- Taux conversion essai → payant  
- Volume facturé via la plateforme (GMV)  
- NPS portail client  
- % clients utilisant l’arabe  

---

## 10. Avantages compétitifs défendables

1. **Parcours bout-en-bout** (pas seulement CRM ou seulement facturation)  
2. **Multi-tenant + white-label** (sous-domaine, thème, PDF) — adapté franchises / groupes  
3. **Portails client & apporteur** — rare sur les alternatives mid-market  
4. **Arabe natif** — avantage vs outils US/EU  
5. **Architecture modulaire** — extensions zip, API, webhooks  
6. **Coût d’entrée SMB** — vs Salesforce / Dynamics  

---

## 11. Risques & mitigations (transparence investisseur)

| Risque | Mitigation |
|---|---|
| Marché saturé (Zoho, Odoo…) | Cibler **niche GCC** : arabe, apporteurs, portail, paiements locaux |
| Conformité facture / données | Partenaire comptable Qatar + hébergeur certifié |
| Dépendance fondateur | Recrutement early team + documentation |
| IA encore partielle | Roadmap IA sur workflows payants uniquement |
| Besoin entité locale | QFC ou LLC dès candidature programme |

---

## 12. Checklist avant dépôt dossier (Qatar)

- [ ] Pitch deck 10–12 slides (EN + version AR optionnelle)  
- [ ] Vidéo démo 3 min (parcours lead → paiement)  
- [ ] `idea.md` + executive summary 1 page  
- [ ] Plan financier 3 ans (scénario conservateur / base)  
- [ ] Structure juridique cible (Qatar vs QFC)  
- [ ] Lettres d’intention ou LOI de 2–3 entreprises pilotes qatariennes  
- [ ] Plan Qatarization & impact (emplois locaux)  
- [ ] Alignement Vision 2030 (digitalisation PME, export de services)  
- [ ] Roadmap produit Qatar (QAR, paiements, données) avec jalons datés  

---

## 13. Résumé exécutif (copier-coller pitch)

**FlowDesk** est une plateforme SaaS B2B multi-tenant qui permet aux entreprises de services de gérer **toute leur chaîne de valeur commerciale** — clients, projets, devis, factures, paiements, portails et commissions partenaires — sur un workspace brandé et multilingue (dont l’arabe).

Nous cherchons à **établir notre hub régional au Qatar** pour servir les PME et réseaux commerciaux du GCC, avec conformité locale, paiements en QAR et hébergement régional. Le produit est **déjà fonctionnel** (MVP avancé) ; l’investissement financera l’**ancrage Qatar**, les **intégrations locales** et l’**acquisition des premiers clients payants**.

---

*Document généré à partir de l’état réel du dépôt FlowDesk SaaS — à personnaliser avec montant levé, équipe, et programme cible (QDB, QFC, QSTP, etc.).*

---

## Documents associés

| Fichier | Description |
|---|---|
| [`pitch-deck.md`](pitch-deck.md) | Outline 10 slides (anglais) + notes orateur + script démo |
| [`idea-en.md`](idea-en.md) | Version anglaise complète pour QDB / Invest Qatar |
| [`executive-summary-en.md`](executive-summary-en.md) | Résumé exécutif 1 page (leave-behind investisseur) |
| [`modules-qatar.md`](modules-qatar.md) | Catalogue modules sectoriels Qatar (immo, e-com, POS, livraison…) |
