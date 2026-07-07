<?php

namespace App\Services;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProjectExampleWorkspaceAiService
{
    public const MAX_TASKS_PER_PROJECT = 12;

    public const MAX_PROJECTS_PER_RUN = 8;

    public const MAX_CLIENTS_PER_RUN = 10;

    public function __construct(
        private PlatformLlmRouter $llm,
    ) {}

    /**
     * One LLM call: parse the user’s natural-language instructions and return JSON
     * with clients (optional) and 1..N projects with title, description HTML, tasks, and optional
     * project/task prices in the company’s default currency.
     * Clients are found by phone, email, or name within the company, or created if missing.
     *
     * @return array{projects: Collection<int, Project>, llm: array<string, mixed>}
     */
    public function createProjectsFromPrompt(Company $company, User $user, string $userPrompt, int $maxProjectsAllowed): array
    {
        $userPrompt = trim($userPrompt);
        if ($userPrompt === '') {
            throw new RuntimeException(__('projects_ai_empty_prompt'));
        }
        if ($maxProjectsAllowed < 1) {
            throw new RuntimeException(__('projects_ai_example_not_enough_slots'));
        }
        $cap = min($maxProjectsAllowed, self::MAX_PROJECTS_PER_RUN);
        $currency = $this->defaultWorkspaceCurrency($company);

        $system = <<<'PROMPT'
You are an expert product and project manager. The user will describe in natural language what they want: one or more projects, optional client names and contact details, tech stack, languages, and task ideas.

Your job is to output ONLY valid JSON (no markdown, no code fences, no text before or after the JSON) that the application will use to create database records.

Output shape:
{
  "clients": [
    { "ref": "stable_id", "name": "Client full name", "phone": "digits or formatted", "email": "optional or null" }
  ],
  "projects": [
    {
      "client_ref": "stable_id or null if internal / no client",
      "title": "short project name",
      "description_html": "<p>...</p>",
      "final_price": null,
      "negotiated_price": null,
      "project_total": null,
      "tasks": [
        {
          "title": "...",
          "description": "one line or empty string"
        }
      ]
    }
  ]
}

Rules for "clients":
- Use "ref" as a short unique id (letters, digits, underscore only; e.g. client_mohamed). Reuse the same "ref" when the user says several projects are for the same person.
- Include a row in "clients" whenever the user provides a name and/or phone/email for a client. Name is required for each client row; phone and email are optional but set them when the user gives them.
- "clients" may be an empty array if no client is needed.

Rules for "projects":
- 1 to MAX_PROJECTS projects (the application will enforce a per-workspace cap).
- "client_ref" must be null, or it must match the "ref" of one entry in "clients". Never invent a "client_ref" without a corresponding client.
- "title" under 200 characters.
- "description_html": HTML fragment only (no <html> or <body>); use <p>, <ul>, <li>, <strong> as needed. Summarize goals, stack (e.g. Laravel), and deliverables if the user mentioned them.
- **Project money (workspace default currency, major units)**: Optional "final_price", "negotiated_price", and/or "project_total" when the user or you infer a project budget or agreed total. Tasks are always created as included in the project package (no per-task invoice line); do not rely on per-task amounts for billing.
- "tasks": 3 to 12 per project, action-oriented task titles; optional "description" (empty string allowed). Do not include per-task prices in JSON.
- Escape double quotes in JSON strings.
PROMPT;
        $system = str_replace('MAX_PROJECTS', (string) $cap, $system);
        $system .= "\n\n".AiAssistantPrompts::workflowJsonOutputLanguageRules();
        $system .= "\n\nWorkspace default currency (ISO 4217) for all monetary numbers in JSON, unless a task sets its own \"currency\" field: ".$currency.'.';

        $userBlock = "User request (follow it closely for project count, clients, prices, and themes):\n\n".$userPrompt;

        $result = $this->llm->complete($system, $userBlock, 20000, $company);
        $decoded = $this->decodeJsonObject($result['suggestion']);
        $projects = $decoded['projects'] ?? null;
        if (! is_array($projects) || $projects === []) {
            throw new RuntimeException(__('projects_ai_json_no_projects'));
        }
        if (count($projects) > $cap) {
            throw new RuntimeException(__('projects_ai_json_too_many_projects', ['max' => (string) $cap]));
        }

        $rawClients = $decoded['clients'] ?? [];
        if (! is_array($rawClients)) {
            $rawClients = [];
        }
        if (count($rawClients) > self::MAX_CLIENTS_PER_RUN) {
            throw new RuntimeException(__('projects_ai_json_too_many_clients', ['max' => (string) self::MAX_CLIENTS_PER_RUN]));
        }

        $this->assertUniqueClientRefs($rawClients);
        $refToClient = [];

        $created = collect();

        DB::transaction(function () use ($company, $user, $rawClients, $projects, &$refToClient, $created, $currency): void {
            foreach ($rawClients as $cr) {
                if (! is_array($cr)) {
                    throw new RuntimeException(__('Invalid project data in AI response.'));
                }
                $ref = isset($cr['ref']) ? trim((string) $cr['ref']) : '';
                if ($ref === '' || ! preg_match('/^[a-zA-Z0-9_]{1,32}$/', $ref)) {
                    throw new RuntimeException(__('projects_ai_client_ref_invalid_format'));
                }
                $name = isset($cr['name']) ? trim((string) $cr['name']) : '';
                if ($name === '') {
                    throw new RuntimeException(__('projects_ai_client_name_missing'));
                }
                $name = Str::limit($name, 255, '');
                $phone = isset($cr['phone']) ? trim((string) $cr['phone']) : '';
                $phone = $phone === '' ? null : Str::limit($phone, 64, '');
                $email = isset($cr['email']) && $cr['email'] !== null && trim((string) $cr['email']) !== ''
                    ? Str::limit(trim((string) $cr['email']), 255, '')
                    : null;
                if (isset($refToClient[$ref])) {
                    throw new RuntimeException(__('projects_ai_client_duplicate_ref'));
                }
                $client = $this->findOrCreateClient($company, $name, $phone, $email);
                $refToClient[$ref] = $client;
            }

            foreach ($projects as $row) {
                if (! is_array($row)) {
                    throw new RuntimeException(__('Invalid project data in AI response.'));
                }
                $title = isset($row['title']) ? trim((string) $row['title']) : '';
                if ($title === '') {
                    throw new RuntimeException(__('A project in the AI response is missing a title.'));
                }
                $title = Str::limit($title, 255, '');

                $htmlRaw = $row['description_html'] ?? $row['description'] ?? null;
                if (! is_string($htmlRaw) || trim($htmlRaw) === '') {
                    throw new RuntimeException(__('A project in the AI response is missing a description.'));
                }
                $html = $this->normalizeDescriptionHtml($htmlRaw);

                $clientRef = $row['client_ref'] ?? null;
                $clientId = null;
                if ($clientRef !== null && (string) $clientRef !== '') {
                    $key = trim((string) $clientRef);
                    if (! isset($refToClient[$key])) {
                        throw new RuntimeException(__('projects_ai_project_client_ref_unknown', ['ref' => $key]));
                    }
                    $clientId = $refToClient[$key]->id;
                }

                $finalPriceMinor = $this->parseOptionalMajorToMinor($row['final_price'] ?? null, $currency);
                $negPriceMinor = $this->parseOptionalMajorToMinor($row['negotiated_price'] ?? null, $currency);
                $totMinor = $this->parseOptionalMajorToMinor(
                    $row['project_total'] ?? $row['total_price'] ?? null,
                    $currency,
                );
                if ($finalPriceMinor === null && $negPriceMinor === null && $totMinor !== null) {
                    $finalPriceMinor = $totMinor;
                    $negPriceMinor = $totMinor;
                }

                $project = Project::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'created_by' => $user->id,
                    'title' => $title,
                    'description' => $html,
                    'status' => ProjectStatus::InProgress,
                    'source' => ProjectSource::Internal,
                    'client_id' => $clientId,
                    'provider_id' => null,
                    'final_price' => $finalPriceMinor,
                    'negotiated_price' => $negPriceMinor,
                ]);

                $items = $row['tasks'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }
                $items = array_slice($items, 0, self::MAX_TASKS_PER_PROJECT);
                $order = 0;
                foreach ($items as $tr) {
                    if (! is_array($tr)) {
                        continue;
                    }
                    $tt = isset($tr['title']) ? trim((string) $tr['title']) : '';
                    if ($tt === '') {
                        continue;
                    }
                    $tt = Str::limit($tt, 255, '');
                    $d = isset($tr['description']) ? trim((string) $tr['description']) : '';
                    $d = $d === '' ? null : Str::limit($d, 5000, '');
                    $order++;
                    ProjectTask::query()->create([
                        'company_id' => $company->id,
                        'project_id' => $project->id,
                        'title' => $tt,
                        'description' => $d,
                        'status' => TaskStatus::Todo,
                        'sort_order' => $order,
                        'scope' => TaskScope::Core,
                        'price_mode' => TaskPriceMode::Bundled,
                        'billable' => false,
                        'amount_cents' => null,
                        'currency' => null,
                    ]);
                }
                if ($order === 0) {
                    throw new RuntimeException(__('A project in the AI response has no valid tasks.'));
                }

                $created->push($project);
            }
        });

        return [
            'projects' => $created,
            'llm' => $result,
        ];
    }

    private function defaultWorkspaceCurrency(Company $company): string
    {
        return flowdesk_normalize_currency_code($company->default_currency ?? 'USD');
    }

    /**
     * Convert a user/model decimal (major units) to minor units, or null if absent / not positive.
     */
    private function parseOptionalMajorToMinor(mixed $value, string $currency): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            if ((float) $value < 0) {
                throw new RuntimeException(__('projects_ai_negative_price'));
            }
            if ((float) $value === 0.0) {
                return null;
            }
            $s = (string) $value;
        } else {
            $s = $this->parseAmountStringToScalar((string) $value);
            if ($s === null) {
                return null;
            }
        }
        if (! is_numeric($s)) {
            return null;
        }
        if ((float) $s < 0) {
            throw new RuntimeException(__('projects_ai_negative_price'));
        }
        $minor = flowdesk_decimal_to_minor($s, $currency);
        if ($minor === null || $minor === 0) {
            return null;
        }

        return $minor;
    }

    private function parseAmountStringToScalar(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }
        $s = str_replace("\u{00A0}", '', $s);
        $s = preg_replace('/\s+/u', '', $s) ?? $s;
        if (preg_match('/^\d+,\d{1,2}$/', $s)) {
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',') && ! str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }
        if (! is_numeric($s)) {
            return null;
        }

        return $s;
    }

    private function findOrCreateClient(Company $company, string $name, ?string $phone, ?string $email): Client
    {
        $existing = $this->findExistingClient($company, $name, $phone, $email);
        if ($existing) {
            return $existing;
        }

        $client = Client::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => null,
        ]);
        app(ClientCodeService::class)->assignIfMissing($client);

        return $client;
    }

    private function findExistingClient(Company $company, string $name, ?string $phone, ?string $email): ?Client
    {
        $base = Client::query()->withoutGlobalScopes()->where('company_id', $company->id);
        $digits = $this->normalizePhoneDigits($phone);
        if ($digits !== '') {
            foreach ($base->clone()->whereNotNull('phone')->get() as $c) {
                if ($this->normalizePhoneDigits($c->phone) === $digits) {
                    return $c;
                }
            }
        }
        if ($email !== null && $email !== '') {
            $found = $base->clone()
                ->whereNotNull('email')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->first();
            if ($found) {
                return $found;
            }
        }
        $nameTrim = trim($name);
        if ($nameTrim === '') {
            return null;
        }

        return $base->clone()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($nameTrim)])
            ->first();
    }

    private function normalizePhoneDigits(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * @param  array<int, mixed>  $rawClients
     */
    private function assertUniqueClientRefs(array $rawClients): void
    {
        $seen = [];
        foreach ($rawClients as $cr) {
            if (! is_array($cr)) {
                continue;
            }
            $ref = isset($cr['ref']) ? trim((string) $cr['ref']) : '';
            if ($ref === '') {
                continue;
            }
            if (isset($seen[$ref])) {
                throw new RuntimeException(__('projects_ai_client_duplicate_ref'));
            }
            $seen[$ref] = true;
        }
    }

    private function normalizeDescriptionHtml(string $raw): string
    {
        $t = trim($raw);
        if ($t === '') {
            throw new RuntimeException(__('The model returned an empty description.'));
        }
        if (str_starts_with($t, '```')) {
            $t = preg_replace('/^```(?:html)?\s*/i', '', $t) ?? $t;
            $t = preg_replace('/\s*```$/', '', $t) ?? $t;
            $t = trim($t);
        }
        if ($t !== '' && ! preg_match('/<[a-z][\s\S]*>/i', $t)) {
            return '<p>'.e($t).'</p>';
        }

        return $t;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $raw): array
    {
        $text = trim($raw);
        if (preg_match('/```(?:json)?\s*([\s\S]*)\s*```/', $text, $m)) {
            $text = trim($m[1]);
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException(__('Could not parse AI response.'));
        }
        $slice = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($slice, true);
        if (! is_array($decoded)) {
            throw new RuntimeException(__('Could not parse AI response.'));
        }

        return $decoded;
    }
}
