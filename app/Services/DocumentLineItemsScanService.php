<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class DocumentLineItemsScanService
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    public function __construct(
        private PlatformLlmRouter $llm,
    ) {}

    /**
     * @return list<array{description: string, quantity: int, unit_amount: int}>
     */
    public function extractLines(UploadedFile $file, string $currency, ?Company $company = null): array
    {
        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException(__('Unsupported file type. Upload a JPEG, PNG, WebP, or PDF.'));
        }

        $bytes = $file->getSize();
        if (! is_int($bytes) || $bytes < 1 || $bytes > self::MAX_BYTES) {
            throw new RuntimeException(__('Document must be smaller than 10 MB.'));
        }

        $contents = $file->get();
        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException(__('Could not read the uploaded document.'));
        }

        $base64 = base64_encode($contents);
        $currency = strtoupper($currency);
        $scale = flowdesk_currency_minor_scale($currency);
        $fd = flowdesk_currency_fraction_digits($currency);

        $system = <<<PROMPT
You are an OCR and data-extraction assistant for commercial documents (invoices, quotes, receipts, purchase orders).
Read the attached document image or PDF and extract every billable line item.

Respond with ONLY valid JSON (no markdown fences):
{"items":[{"description":"...","quantity":1,"unit_price_major":0}]}

Rules:
- 1 to 30 line items. Skip subtotals, tax lines, payment terms, and headers unless they are real products/services.
- description: clear commercial line text as shown on the document.
- quantity: positive integer (default 1 if not shown).
- unit_price_major: unit price in major currency units as a number (e.g. 1250.5 for TND, 99.99 for EUR). Up to {$fd} decimals for {$currency}.
- Prefer HT / ex-VAT unit prices when both HT and TTC appear.
- If only line totals are visible, derive unit_price_major = line_total / quantity.
- Use the same language as the document for descriptions.
- Target currency context: {$currency}. Convert only when the document currency is explicit and different.
PROMPT;
        $system .= "\n\n".AiAssistantPrompts::outputLanguageInstruction();

        $user = 'Extract all line items with quantities and unit prices from this document.';

        $result = $this->llm->completeWithDocument($system, $user, $base64, $mime, 4096, $company);
        $decoded = $this->decodeJsonObject($result['suggestion']);
        $rows = $decoded['items'] ?? [];
        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException(__('No line items were found in the document.'));
        }

        $out = [];
        foreach (array_slice($rows, 0, 30) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $desc = trim((string) ($row['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $major = (float) ($row['unit_price_major'] ?? $row['unit_price'] ?? 0);
            if ($major < 0) {
                $major = 0;
            }
            $minor = (int) round($major * $scale);
            $out[] = [
                'description' => mb_substr($desc, 0, 500),
                'quantity' => $qty,
                'unit_amount' => $minor,
            ];
        }

        if ($out === []) {
            throw new RuntimeException(__('Could not parse valid line items from the document.'));
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $raw): array
    {
        $trim = trim($raw);
        if (preg_match('/```(?:json)?\s*(.*?)```/s', $trim, $m)) {
            $trim = trim($m[1]);
        }
        $decoded = json_decode($trim, true);
        if (! is_array($decoded)) {
            throw new RuntimeException(__('Could not parse the AI response.'));
        }

        return $decoded;
    }
}
