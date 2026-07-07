<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScansDocumentLineItems;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\AiCreditUsageService;
use App\Services\DocumentLineItemsScanService;
use App\Services\InvoiceLineItemsAiService;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class InvoiceAiController extends Controller
{
    use ScansDocumentLineItems;

    public function suggestDraft(
        Request $request,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        InvoiceLineItemsAiService $linesAi,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'brief' => ['required', 'string', 'min:10', 'max:4000'],
            'replace' => ['sometimes', 'boolean'],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency)],
            'client_id' => ['nullable', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
        ]);

        $creditCost = $usage->creditsForTask('invoice_line_items');
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        $client = isset($data['client_id'])
            ? Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->find($data['client_id'])
            : null;

        try {
            $items = $linesAi->suggestLinesDraft($data['brief'], $data['currency'], $client, null, $company);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $usage->recordForTask($company, 'invoice_line_items');

        return $this->jsonItems($items, strtoupper($data['currency']), $creditCost);
    }

    public function suggestLineItems(
        Request $request,
        Invoice $invoice,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        InvoiceLineItemsAiService $linesAi,
    ): JsonResponse {
        $this->authorizeInvoice($invoice);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'brief' => ['required', 'string', 'min:10', 'max:4000'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        $creditCost = $usage->creditsForTask('invoice_line_items');
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        try {
            $items = $linesAi->suggestLines($invoice, $data['brief']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $usage->recordForTask($company, 'invoice_line_items');

        $currency = strtoupper((string) $invoice->currency);

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
            'invoice_line_items_scan',
            strtoupper($currency),
            $planLimits,
            $usage,
            $scan,
        );
    }

    public function scanLineItems(
        Request $request,
        Invoice $invoice,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        DocumentLineItemsScanService $scan,
    ): JsonResponse {
        $this->authorizeInvoice($invoice);

        return $this->scanDocumentForLines(
            $request,
            'invoice_line_items_scan',
            strtoupper((string) $invoice->currency),
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

    private function authorizeInvoice(Invoice $invoice): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $invoice->company_id !== (string) $company->id, 403);
    }
}
