<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AiCreditUsageService;
use App\Services\DocumentLineItemsScanService;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

trait ScansDocumentLineItems
{
    /**
     * @return list<array{description: string, quantity: int, unit_amount: int}>
     */
    protected function scanDocumentForLines(
        Request $request,
        string $creditTask,
        string $currency,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        DocumentLineItemsScanService $scan,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        $creditCost = $usage->creditsForTask($creditTask);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        try {
            $items = $scan->extractLines($data['document'], $currency, $company);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $usage->recordForTask($company, $creditTask);

        return response()->json([
            'items' => array_map(static function (array $row) use ($currency): array {
                return [
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_amount' => $row['unit_amount'],
                    'unit_major' => flowdesk_minor_to_major((int) $row['unit_amount'], $currency),
                ];
            }, $items),
            'disclaimer' => __('AI-extracted from document — review all lines and prices before sending.'),
            'ai_credits_charged' => $creditCost,
        ]);
    }
}
