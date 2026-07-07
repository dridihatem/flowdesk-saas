<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScansDocumentLineItems;
use App\Models\Client;
use App\Models\Project;
use App\Models\Proposal;
use App\Services\AiCreditUsageService;
use App\Services\DocumentLineItemsScanService;
use App\Services\PlanLimitService;
use App\Services\ProposalQuoteLinesAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class ProposalAiController extends Controller
{
    use ScansDocumentLineItems;

    public function suggestDraft(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        ProposalQuoteLinesAiService $linesAi,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'brief' => ['required', 'string', 'min:10', 'max:4000'],
            'replace' => ['sometimes', 'boolean'],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency)],
            'name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'project_id' => ['nullable', 'string', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'valid_until' => ['nullable', 'date'],
        ]);

        $creditCost = $usage->creditsForTask('quote_line_items');
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        $client = isset($data['client_id'])
            ? Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->find($data['client_id'])
            : null;
        $project = isset($data['project_id'])
            ? Project::query()->withoutGlobalScopes()->where('company_id', $company->id)->find($data['project_id'])
            : null;

        try {
            $items = $linesAi->suggestLinesDraft(
                $data['brief'],
                $data['currency'],
                $data['name'] ?? null,
                $client?->name,
                $project?->title,
                isset($data['valid_until']) ? (string) $data['valid_until'] : null,
                $company,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $usage->recordForTask($company, 'quote_line_items');

        return $this->jsonItems($items, strtoupper($data['currency']), $creditCost);
    }

    public function suggestLineItems(
        Request $request,
        Proposal $proposal,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        ProposalQuoteLinesAiService $linesAi,
    ): JsonResponse {
        $this->authorizeProposal($proposal);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'brief' => ['required', 'string', 'min:10', 'max:4000'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        $creditCost = $usage->creditsForTask('quote_line_items');
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        try {
            $items = $linesAi->suggestLines($proposal, $data['brief']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $usage->recordForTask($company, 'quote_line_items');

        $currency = strtoupper((string) $proposal->currency);

        return $this->jsonItems($items, $currency, $creditCost);
    }

    public function scanDraft(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        DocumentLineItemsScanService $scan,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $currency = $request->validate([
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency)],
        ])['currency'];

        return $this->scanDocumentForLines(
            $request,
            'quote_line_items_scan',
            strtoupper($currency),
            $planLimits,
            $usage,
            $scan,
        );
    }

    public function scanLineItems(
        Request $request,
        Proposal $proposal,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        DocumentLineItemsScanService $scan,
    ): JsonResponse {
        $this->authorizeProposal($proposal);

        return $this->scanDocumentForLines(
            $request,
            'quote_line_items_scan',
            strtoupper((string) $proposal->currency),
            $planLimits,
            $usage,
            $scan,
        );
    }

    /**
     * @param  list<array{description: string, quantity: int, unit_amount: int}>  $items
     */
    private function jsonItems(array $items, string $currency, int $creditCost): JsonResponse
    {
        return response()->json([
            'items' => array_map(static function (array $row) use ($currency): array {
                return [
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_amount' => $row['unit_amount'],
                    'unit_major' => flowdesk_minor_to_major((int) $row['unit_amount'], $currency),
                ];
            }, $items),
            'disclaimer' => __('AI-generated content — review prices before sending to clients.'),
            'ai_credits_charged' => $creditCost,
        ]);
    }

    private function authorizeProposal(Proposal $proposal): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $proposal->company_id !== (string) $company->id, 403);
    }
}
