<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NovaQuestionIntentService
{
    /**
     * @return array{
     *   intent: 'workspace'|'general',
     *   use_web_search: bool,
     *   project_ids: list<string>,
     *   client_ids: list<string>
     * }
     */
    public function analyze(Company $company, string $message): array
    {
        $normalized = Str::lower(trim($message));
        if ($normalized === '') {
            return [
                'intent' => 'workspace',
                'use_web_search' => false,
                'project_ids' => [],
                'client_ids' => [],
            ];
        }

        $projects = $this->matchingProjects($company, $normalized);
        $clients = $this->matchingClients($company, $normalized);

        $workspaceScore = $this->scoreWorkspaceKeywords($normalized);
        $generalScore = $this->scoreGeneralKeywords($normalized);

        if ($projects->isNotEmpty() || $clients->isNotEmpty()) {
            $workspaceScore += 3;
        }

        $intent = ($generalScore > $workspaceScore && $projects->isEmpty() && $clients->isEmpty())
            ? 'general'
            : 'workspace';

        return [
            'intent' => $intent,
            'use_web_search' => $intent === 'general',
            'project_ids' => $projects->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
            'client_ids' => $clients->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
        ];
    }

    /**
     * @return Collection<int, Project>
     */
    private function matchingProjects(Company $company, string $message): Collection
    {
        $projects = Project::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderByDesc('updated_at')
            ->limit(80)
            ->get(['id', 'title']);

        return $projects->filter(function (Project $project) use ($message) {
            $title = Str::lower(trim((string) $project->title));
            if ($title === '' || mb_strlen($title) < 3) {
                return false;
            }

            return str_contains($message, $title)
                || $this->containsSignificantTokens($message, $title);
        })->values();
    }

    /**
     * @return Collection<int, Client>
     */
    private function matchingClients(Company $company, string $message): Collection
    {
        $clients = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderByDesc('updated_at')
            ->limit(80)
            ->get(['id', 'name']);

        $matched = $clients->filter(function (Client $client) use ($message) {
            $name = Str::lower(trim((string) $client->name));
            if ($name === '' || mb_strlen($name) < 3) {
                return false;
            }

            return str_contains($message, $name)
                || $this->containsSignificantTokens($message, $name);
        })->values();

        if ($matched->isNotEmpty()) {
            return $matched;
        }

        $candidate = app(NovaClientAnalysisService::class)->extractClientNameCandidate($message);
        if ($candidate === null) {
            return $matched;
        }

        $needle = Str::lower(trim($candidate));
        $byCandidate = $clients->filter(function (Client $client) use ($needle) {
            $name = Str::lower(trim((string) $client->name));

            return $name !== '' && (str_contains($name, $needle) || str_contains($needle, $name));
        })->values();

        return $byCandidate->take(1);
    }

    private function containsSignificantTokens(string $message, string $phrase): bool
    {
        $tokens = preg_split('/\s+/u', $phrase, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hits = 0;
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 4) {
                continue;
            }
            if (str_contains($message, $token)) {
                $hits++;
            }
        }

        return $hits >= 2 || ($hits === 1 && count($tokens) === 1);
    }

    private function scoreWorkspaceKeywords(string $message): int
    {
        $patterns = [
            'client', 'clients', 'project', 'projects', 'invoice', 'invoices', 'quote', 'quotes',
            'proposal', 'proposals', 'revenue', 'payment', 'payments', 'task', 'tasks', 'calendar',
            'booking', 'bookings', 'deadline', 'unpaid', 'outstanding', 'inquiry', 'inquiries',
            'ticket', 'tickets', 'provider', 'providers', 'workspace', 'flowdesk', 'company',
            'dashboard', 'analytics', 'report', 'reports', 'billing', 'subscription',
            'projet', 'projets', 'client', 'clients', 'facture', 'factures', 'devis', 'tache',
            'taches', 'calendrier', 'rendez-vous', 'revenu', 'paiement', 'impaye',
            'proyecto', 'proyectos', 'factura', 'facturas', 'presupuesto', 'tarea', 'tareas',
            'عميل', 'عملاء', 'مشروع', 'مشاريع', 'فاتورة', 'فواتير', 'مهمة', 'مهام', 'تقويم',
        ];

        return $this->countPatternHits($message, $patterns);
    }

    private function scoreGeneralKeywords(string $message): int
    {
        $patterns = [
            'who is', 'who was', 'what is the name', 'what song', 'which song', 'lyrics',
            'capital of', 'weather in', 'weather for', 'recipe for', 'movie', 'film', 'actor',
            'actress', 'band', 'album', 'song', 'music', 'metallica', 'beatles', 'sport',
            'football', 'soccer', 'basketball', 'news about', 'latest news', 'wikipedia',
            'history of', 'when was', 'when did', 'how old is', 'population of', 'president of',
            'qui est', 'quelle chanson', 'quelle musique', 'meteo', 'météo', 'recette',
            'film', 'acteur', 'groupe', 'album', 'chanson', 'musique',
            'quien es', 'cancion', 'canción', 'musica', 'música', 'pelicula', 'película',
            'من هو', 'أغنية', 'اغنية', 'موسيقى', 'فيلم',
        ];

        return $this->countPatternHits($message, $patterns);
    }

    /**
     * @param  list<string>  $patterns
     */
    private function countPatternHits(string $message, array $patterns): int
    {
        $score = 0;
        foreach ($patterns as $pattern) {
            if (str_contains($message, $pattern)) {
                $score++;
            }
        }

        return $score;
    }
}
