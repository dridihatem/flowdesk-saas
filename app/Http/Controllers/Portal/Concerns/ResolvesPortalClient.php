<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Proposal;
use Illuminate\Http\Request;

trait ResolvesPortalClient
{
    protected function portalClient(Request $request): Client
    {
        $user = $request->user();
        abort_if(! $user || ! $user->hasRole('client'), 403);
        $client = $user->clientProfile;
        abort_if(! $client, 403);

        return $client;
    }

    protected function authorizePortalProject(Client $client, Project $project): void
    {
        abort_if((string) $project->company_id !== (string) $client->company_id, 403);
        abort_if((string) $project->client_id !== (string) $client->id, 403);
    }

    protected function authorizePortalInvoice(Client $client, Invoice $invoice): void
    {
        abort_if((string) $invoice->company_id !== (string) $client->company_id, 403);
        abort_if((string) $invoice->client_id !== (string) $client->id, 403);
    }

    protected function authorizePortalProposal(Client $client, Proposal $proposal): void
    {
        abort_if((string) $proposal->company_id !== (string) $client->company_id, 403);
        abort_if((string) $proposal->client_id !== (string) $client->id, 403);
    }

    protected function authorizePortalTask(Client $client, Project $project, ProjectTask $task): void
    {
        $this->authorizePortalProject($client, $project);
        abort_if((string) $task->project_id !== (string) $project->id, 403);
    }

    protected function authorizePortalQuoteRequest(Client $client, Inquiry $inquiry): void
    {
        abort_if((string) $inquiry->company_id !== (string) $client->company_id, 403);
        abort_if((string) $inquiry->client_id !== (string) $client->id, 403);
        abort_if($inquiry->source !== 'client_portal', 403);
    }
}
