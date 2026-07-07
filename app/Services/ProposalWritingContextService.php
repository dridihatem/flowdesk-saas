<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Models\Project;

class ProposalWritingContextService
{
    /**
     * Build a starter brief for proposal writing mode from a workspace client.
     */
    public function contextForClient(Company $company, ?string $clientId): string
    {
        if ($clientId === null || $clientId === '') {
            return '';
        }

        $client = Client::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('id', $clientId)
            ->first();

        if (! $client) {
            return '';
        }

        $lines = [
            __('Client').': '.$client->name,
        ];

        if ($client->email) {
            $lines[] = __('Email').': '.$client->email;
        }
        if ($client->phone) {
            $lines[] = __('Phone').': '.$client->phone;
        }
        if ($client->code) {
            $lines[] = __('Client code').': '.$client->code;
        }

        $projects = Project::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->latest()
            ->limit(3)
            ->get(['title', 'status']);

        if ($projects->isNotEmpty()) {
            $lines[] = __('Recent projects').': '.$projects->map(fn (Project $p) => $p->title)->implode(', ');
        }

        $lines[] = '';
        $lines[] = __('Service / scope').': ';
        $lines[] = __('Budget range').': ';
        $lines[] = __('Deadline').': ';
        $lines[] = __('Key deliverables').': ';

        return implode("\n", $lines);
    }
}
