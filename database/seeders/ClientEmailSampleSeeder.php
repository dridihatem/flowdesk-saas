<?php

namespace Database\Seeders;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\Provider;
use App\Models\User;
use App\Services\ClientCodeService;
use Illuminate\Database\Seeder;

class ClientEmailSampleSeeder extends Seeder
{
    public function run(): void
    {
        $code = app(ClientCodeService::class);

        foreach ($this->catalog() as $subdomain => $rows) {
            $company = Company::query()->where('subdomain', $subdomain)->first();
            if (! $company) {
                continue;
            }

            foreach ($rows as $row) {
                $client = Client::query()->firstOrCreate(
                    ['company_id' => $company->id, 'email' => $row['email']],
                    [
                        'user_id' => null,
                        'name' => $row['name'],
                        'phone' => $row['phone'] ?? null,
                        'address' => $row['address'] ?? null,
                    ],
                );
                $code->assignIfMissing($client);
            }
        }

        $daweb = Company::query()->where('subdomain', 'dawebcompany')->first();
        if ($daweb) {
            $this->seedProviderSampleProject(
                $daweb,
                'billing@acme-export.tn',
                'Acme export — partner-led rollout',
            );
        }

        $demo = Company::query()->where('subdomain', 'demo')->first();
        if ($demo) {
            $this->seedProviderSampleProject(
                $demo,
                'client-a@demo.local',
                'Demo project — provider assignment',
            );
        }
    }

    /**
     * @return array<string, list<array{name: string, email: string, phone?: string|null, address?: array<string, mixed>|null}>>
     */
    private function catalog(): array
    {
        return [
            'dawebcompany' => [
                [
                    'name' => 'Acme Export SA',
                    'email' => 'billing@acme-export.tn',
                    'phone' => '+216 71 111 222',
                    'address' => ['line1' => 'Route de l\'aéroport', 'city' => 'Sfax', 'country' => 'TN'],
                ],
                [
                    'name' => 'Phoenix Retail',
                    'email' => 'ops@phoenix-retail.tn',
                    'phone' => '+216 72 333 444',
                ],
                [
                    'name' => 'Capitol Events',
                    'email' => 'events@capitol-events.tn',
                    'phone' => '+216 73 555 666',
                ],
            ],
            'flowdeskstudio' => [
                [
                    'name' => 'Fabrikam Digital',
                    'email' => 'hello@fabrikam-digital.fr',
                    'phone' => '+33 1 42 00 00 01',
                    'address' => ['line1' => '12 Rue Lafayette', 'city' => 'Lyon', 'country' => 'FR'],
                ],
                [
                    'name' => 'Tailspin Toys EU',
                    'email' => 'b2b@tailspin-eu.com',
                    'phone' => '+32 2 000 00 00',
                ],
            ],
            'globex' => [
                [
                    'name' => 'Globex Procurement',
                    'email' => 'procurement@globex.com',
                    'phone' => '+1 212 555 0100',
                ],
                [
                    'name' => 'Globex Legal',
                    'email' => 'legal-notices@globex.com',
                    'phone' => '+1 212 555 0199',
                ],
            ],
            'demo' => [
                [
                    'name' => 'Sample Client A',
                    'email' => 'client-a@demo.local',
                    'phone' => '+216 90 000 001',
                ],
                [
                    'name' => 'Sample Client B',
                    'email' => 'client-b@demo.local',
                    'phone' => '+216 90 000 002',
                ],
                [
                    'name' => 'Sample Client C',
                    'email' => 'client-c@demo.local',
                    'phone' => '+216 90 000 003',
                ],
            ],
        ];
    }

    private function seedProviderSampleProject(Company $company, string $clientEmail, string $projectTitle): void
    {
        $provider = Provider::query()->where('company_id', $company->id)->first();
        if (! $provider) {
            return;
        }

        $client = Client::query()
            ->where('company_id', $company->id)
            ->where('email', $clientEmail)
            ->first();
        if (! $client) {
            return;
        }

        $owner = User::query()
            ->where('company_id', $company->id)
            ->role('company_admin')
            ->first()
            ?? User::query()->where('company_id', $company->id)->first();
        if (! $owner) {
            return;
        }

        Project::query()->firstOrCreate(
            ['company_id' => $company->id, 'title' => $projectTitle],
            [
                'client_id' => $client->id,
                'provider_id' => $provider->id,
                'created_by' => $owner->id,
                'status' => ProjectStatus::Pending,
                'source' => ProjectSource::Internal,
                'description' => 'Sample work assigned to the business provider for workspace demos.',
                'final_price' => 2500,
                'negotiated_price' => 2500,
                'final_deadline' => now()->addWeeks(4)->toDateString(),
            ],
        );
    }
}
