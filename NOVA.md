# Nova — AI business assistant

Nova is FlowDesk’s workspace AI assistant. She answers questions about **your company data**: clients, projects, invoices, quotes, revenue, payments, tasks, meetings, team size, and upcoming calendar events.

Display name: **`{CompanyName} Nova`** (brand name configurable via `FLOWDESK_AI_ASSISTANT_NAME`, default `Nova`).

---

## Supported languages

Nova voice and chat work in **six UI languages**. Set your workspace language in **Settings → Locale**; phrase packs live in `lang/{locale}/nova_voice.php`.

| Code | Language | Voice navigation | Briefing | Workflows |
|------|----------|------------------|----------|-----------|
| `en` | English | ✓ | ✓ | ✓ |
| `fr` | French | ✓ | ✓ | ✓ |
| `es` | Spanish | ✓ | — | partial (EN fallbacks) |
| `ar` | Arabic | ✓ | ✓ | partial (EN fallbacks) |
| `hi` | Hindi | ✓ | ✓ | ✓ |
| `id` | Indonesian | ✓ | ✓ | ✓ |

**Tip:** Speak in the same language as your UI for best recognition. English phrases are also accepted in most locales as fallbacks.

---

## Where to find Nova

| Location | What you get |
|----------|----------------|
| **Top bar → Nova mic** | Always-on voice navigation on every workspace page |
| **Sidebar → Nova** (`/assistant`) | Full assistant: voice, chat, summary, conversation history, writing modes |
| **Dashboard** | Compact Nova card + summary widget (when enabled in dashboard layout) |

Requires a plan that includes **AI credits** and a platform LLM key configured by the platform admin. Human voice replies need a **Google (Gemini) API key** (recommended, Gemini Flash TTS) or an **OpenAI API key** in platform settings.

---

## Voice navigation

The **Nova** mic in the **top bar** is always listening (Chrome/Edge recommended).

### Wake phrase

1. Say **“Nova”** or **“Hey Nova”** (FR / ES / AR / HI / ID variants supported).
2. A **tooltip** appears: **“Hello {YourName} · {CompanyName}”** and credit info.
3. Nova speaks: **“Hello {YourName}.”** (OpenAI / Gemini TTS when configured).
4. Within ~20 seconds, say where to go — e.g. **“rapport”**, **“audiences”**, **“create invoice”**.

You can also say a destination **directly** without the wake phrase.

### Stop and resume listening

| Say | Effect |
|-----|--------|
| **stop listening**, **don't listen** (FR: *arrête d'écouter*) | Mic turns **red**; Nova says *“I'm not listening.”* |
| **start listening**, wake word, or click mic | Listening resumes; Nova says *“I'm listening again.”* |

While paused, navigation and chat commands are ignored until you resume.

### Credits & timing

| Action | Credits | Notes |
|--------|---------|--------|
| **Voice navigation** (go to a page) | **Free** | No credits charged |
| **Wake voice reply** (TTS) | **5 credits** | Gemini Flash TTS (default) or OpenAI TTS |
| **Identity reply** (“Who are you?”) | **Free** | Canned introduction |
| **Client analysis** (see below) | **Free** | Canned workspace summary |
| **Assistant chat** | **75 credits** | Per message (`nova_chat`) |
| **Complete voice briefing** | **15 credits** | Full spoken workspace report (`nova_briefing`) |

Voice reply playback usually takes **1–3 seconds** after you say Nova (network + TTS).

### Complete voice briefing

Say a briefing phrase after the wake word (or directly):

| Language | Example phrase |
|----------|----------------|
| EN | “Give me a complete analysis” |
| FR | “Donne-moi une analyse complète” |
| HI | “पूर्ण विश्लेषण दें” |
| ID | “Berikan analisis lengkap” |

Nova speaks a full summary of your workspace:

- Revenue this month and growth vs last month  
- Clients and active projects  
- Pending projects (with names)  
- Unpaid / overdue invoices  
- Open and overdue tasks  
- Upcoming calendar events (next 7 days)  
- Open inquiries and support tickets  

---

## Client analysis (voice & chat)

Ask Nova to **analyze clients** — by voice (after wake word) or in the assistant chat. **No AI credits** are charged; Nova reads live workspace data and speaks a structured summary.

### All clients (overview)

| Language | Example |
|----------|---------|
| EN | “Analyze clients” |
| FR | “Analyser les clients” |
| HI | “ग्राहकों का विश्लेषण करें” |
| ID | “Analisis klien” |

Returns: total clients, who has unpaid invoices, who has meetings, and the most active clients.

### One client by name

| Language | Example |
|----------|---------|
| EN | “Analyze client Acme Holdings” |
| FR | “Analyser le client Acme Holdings” |
| HI | “ग्राहक Acme Holdings का विश्लेषण करें” |
| ID | “Analisis klien Acme Holdings” |

Also works: *“Analyze client called …”*, *“Analyse the client …”*.

**Per-client report includes:**

- Profile (status, email, phone, portal account yes/no)  
- Projects (count + recent list)  
- Invoices (paid / unpaid / overdue, outstanding balance)  
- Quotes / proposals  
- **Meetings & calls** — upcoming and past, with or without video link (Google Meet, Zoom, custom URL)  
- Team notes and feedback counts  

Requires permission **`workspace.manage_clients`**. If the name is not found, Nova asks you to try the full client name.

---

## Voice workflows (multi-step)

Say a workflow phrase after the wake word. Nova asks step-by-step questions, creates the record, speaks success, and redirects to the list.

| Workflow | Example phrases (EN) | Also in |
|----------|----------------------|---------|
| **Create client** (+ optional portal) | “Create client account”, “Add client account” | FR, HI, ID |
| **Add HR employee** | “Create employee”, “Add HR employee” | FR, HI, ID |
| **Create provider** | “Create provider account” | FR, HI, ID |
| **Change VAT rate** | “Change VAT”, “Update VAT rate” | FR |
| **Change workspace language** | “Change language”, “Change workspace language” | FR |

Workflow prompts and success messages are translated per locale (`nova_workflow_*` keys in `lang/{locale}.json`).

---

## Identity (“Who are you?”)

| Language | Example |
|----------|---------|
| EN | “Who are you?”, “What can you do?” |
| FR | “Qui es-tu?”, “Que peux-tu faire?” |
| HI | “तुम कौन हो?”, “तुम क्या कर सकते हो?” |
| ID | “Siapa kamu?”, “Apa yang bisa kamu lakukan?” |

Nova introduces herself and lists capabilities. **Free** — no chat credits.

---

## What you can say (navigation)

Say the page name in **English, French, Spanish, Arabic, Hindi, or Indonesian** (matching your UI language works best). Examples:

### General

| Say | Goes to |
|-----|---------|
| dashboard, home, tableau de bord, **beranda**, **डैशबोर्ड** | Dashboard |
| calendar, calendrier, **kalender**, **कैलेंडर** | Calendar |
| settings, paramètres, **pengaturan**, **सेटिंग्स** | Workspace settings |
| profile, mon compte, **profil**, **प्रोफ़ाइल** | Your account |
| assistant, **asisten**, **सहायक** | Nova assistant page |

### Clients & projects

| Say | Goes to |
|-----|---------|
| clients, liste clients, **klien**, **ग्राहक** | Client list |
| create client, nouveau client, **buat klien**, **ग्राहक जोड़ें** | New client |
| projects, projets, **proyek**, **परियोजनाएँ** | Project list |
| create project, nouveau projet, **buat proyek**, **परियोजना बनाएँ** | New project |
| inquiries, demandes, **permintaan**, **पूछताछ** | Inquiries |

### Sales & billing

| Say | Goes to |
|-----|---------|
| invoices, factures, **faktur**, **चालान** | Invoice list |
| create invoice, nouvelle facture, **buat faktur**, **चालान बनाएँ** | New invoice |
| proposals, devis, **penawaran**, **प्रस्ताव** | Proposals / quotes |
| create quote, creer devis, **buat penawaran**, **प्रस्ताव बनाएँ** | New quote |
| billing, abonnement, **langganan**, **बिलिंग** | Plan subscription |

### Insights

| Say | Goes to |
|-----|---------|
| analytics, analytique, **analitik**, **विश्लेषण** | Analytics |
| report, rapport, **laporan**, **रिपोर्ट** | Reports |

### Marketing & email

| Say | Goes to |
|-----|---------|
| marketing, **pemasaran**, **मार्केटिंग** | Marketing hub |
| campaigns, campagnes, **kampanye**, **अभियान** | Email campaigns |
| templates, modèles email, **template email**, **टेम्पलेट** | Email templates |
| audiences, audiances, **audiens**, **दर्शक** | Email audiences |
| forms, formulaires, **formulir**, **फ़ॉर्म** | Lead forms |

### Support & team

| Say | Goes to |
|-----|---------|
| messages, chat, **pesan**, **संदेश** | Messages |
| tickets, **tiket**, **टिकट** | Support tickets |
| providers, apporteurs, **mitra**, **प्रदाता** | Business providers |
| team, équipe, **tim**, **टीम** | Team settings |

Commands respect your **role**, **plan features**, and permissions. If a page is not on your plan, Nova cannot open it.

### Voice + AI on create forms (invoice, quote)

When Nova opens **New invoice** or **New quote** by voice, **AI dictation starts automatically** (Nova top bar pauses while the field mic is active).

| Step | What to do |
|------|------------|
| 1 | Say **« nouvelle facture »** or **« create invoice »** |
| 2 | On the create page, speak your AI brief into the textarea (mic auto-starts) |
| 3 | Optional field hints in the same session: **« devise euro »**, **« client Acme »**, **« titre Projet site web »** |
| 4 | Say **« générer »** / **« generate »** at the end to run AI line items |

You can also click **Start voice input** manually on any AI textarea (invoice, quote, project description, email templates, writing modes).

---

## Assistant page (chat)

Open **`/assistant`** or use the top-bar mic in command mode after the wake word.

Nova answers **two kinds of questions**:

| Type | Examples |
|------|----------|
| **Your workspace** | Monthly revenue, top clients, unpaid invoices, active projects, calendar this week, **analyze client {name}** |
| **General business** | CRM tips, cash collection strategy, how to structure a proposal, vertical ideas, FlowDesk how-to |

When data is in your workspace snapshot, Nova uses real numbers and names. For general questions she uses business knowledge and says when she is not reading live data.

**Free canned replies** (no credits): identity questions, client analysis phrases.

Installed **modules** (zip) appear in the sidebar when uploaded. Nova can still discuss module ideas from [`modules-international.md`](modules-international.md) even if you have not installed them.

### Tabs on `/assistant`

| Tab | Purpose |
|-----|---------|
| **Chat & voice** | Ask Nova about live workspace data |
| **Writing modes** | Proposal, pricing, email, SEO, growth advisors |

### Proposal writing mode (`#mode=proposal`)

On `/assistant#mode=proposal`:

- Pick a **client** from the workspace  
- Enter a **quote title** and brief  
- **Generate line items** with AI  
- **Create quote from outline** — opens the quote editor pre-filled  
- **Listen with Nova** — speaks the generated outline  

Uses AI credits for generation steps; navigation to the quote editor is free.

Example chat questions: monthly revenue, top clients, unpaid invoices, active projects, calendar this week, analyze client Acme Holdings.

Each standard chat message uses **75 AI credits** by default.

---

## Admin setup (human voice)

1. **Admin → Platform settings**
2. Add **OpenAI API key** and/or **Google (Gemini) API key**
3. Choose **Nova voice (OpenAI TTS)** — default voice `nova`, model `tts-1-hd`

Without cloud TTS, the browser’s built-in voice is used as fallback.

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| No voice when saying Nova | Click the Nova mic once to unlock audio; check OpenAI / Gemini key in platform settings. |
| Mic is red / not responding | Say **“start listening”** or click the mic to resume. |
| “rapport” / “audiences” not working | Use the wake phrase first, then the command; check your plan includes Reports / Email marketing. |
| Page not recognized | Try a synonym from the tables above; speak clearly in your UI language. |
| Client analysis not found | Use the full client name; check `workspace.manage_clients` permission. |
| Mic not listening | Allow microphone in browser; use Chrome or Edge. |

---

## Technical reference

| Area | Path |
|------|------|
| Voice navigation | `app/Services/NovaVoiceNavigationService.php` |
| Voice workflows | `app/Services/NovaVoiceWorkflowService.php` |
| Client analysis | `app/Services/NovaClientAnalysisService.php` |
| Identity replies | `app/Services/NovaIdentityService.php` |
| Chat & workspace context | `app/Services/NovaAssistantService.php` |
| Intent routing (workspace vs general) | `app/Services/NovaQuestionIntentService.php` |
| Voice briefing | `app/Services/NovaVoiceBriefingService.php` |
| Module catalog (international) | `modules-international.md` |
| Phrase packs | `lang/{locale}/nova_voice.php` |
| Briefing phrases | `lang/{locale}/nova_briefing.php` |
| OpenAI TTS | `app/Services/OpenAiTextToSpeechService.php` |
| Frontend (top bar) | `resources/js/nova-voice-nav.js`, `nova-voice-matching.js`, `nova-voice-speak.js` |
| Assistant UI | `resources/js/nova-assistant.js`, `ai-writing-modes.js` |
| Credit config | `config/flowdesk.php` → `ai_task_credits.assistant.modes` |
