<?php

namespace App\Services;

use App\Enums\ClientSource;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class WorkspaceApiImportService
{
    public function __construct(
        private ClientCodeService $clientCodes,
        private PlanLimitService $planLimits,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{clients: list<array<string, mixed>>, projects: list<array<string, mixed>>}
     */
    public function import(Company $company, array $payload): array
    {
        return DB::transaction(function () use ($company, $payload) {
            $refToClientId = [];
            $createdClients = [];

            foreach ($payload['clients'] ?? [] as $row) {
                $client = Client::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'name' => $row['name'],
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'source' => ClientSource::Other,
                ]);
                $this->clientCodes->assignIfMissing($client);

                $ref = isset($row['ref']) && is_string($row['ref']) ? trim($row['ref']) : '';
                if ($ref !== '') {
                    $refToClientId[$ref] = $client->id;
                }

                $createdClients[] = [
                    'id' => $client->id,
                    'ref' => $ref !== '' ? $ref : null,
                    'name' => $client->name,
                    'code' => $client->code,
                ];
            }

            $createdProjects = [];
            foreach ($payload['projects'] ?? [] as $row) {
                $this->planLimits->assertAllows($company, 'projects');

                $clientId = $row['client_id'] ?? null;
                if ($clientId === null && ! empty($row['client_ref'])) {
                    $clientId = $refToClientId[$row['client_ref']] ?? null;
                }
                if ($clientId !== null) {
                    $exists = Client::query()->withoutGlobalScopes()
                        ->where('company_id', $company->id)
                        ->whereKey($clientId)
                        ->exists();
                    abort_unless($exists, 422, __('Client not found in this workspace.'));
                }

                $status = ProjectStatus::Draft;
                if (! empty($row['status'])) {
                    $status = ProjectStatus::tryFrom((string) $row['status']) ?? ProjectStatus::Draft;
                }

                $project = Project::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'client_id' => $clientId,
                    'title' => $row['title'],
                    'description' => $row['description'] ?? null,
                    'status' => $status,
                    'source' => ProjectSource::Internal,
                    'created_by' => null,
                ]);

                $createdProjects[] = [
                    'id' => $project->id,
                    'title' => $project->title,
                    'client_id' => $project->client_id,
                ];
            }

            return [
                'clients' => $createdClients,
                'projects' => $createdProjects,
            ];
        });
    }
}
