<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Api\Concerns\ResolvesApiWorkspace;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ClientCodeService;
use App\Services\InvoiceReferenceService;
use App\Services\InvoiceTotalsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceApiController extends Controller
{
    use ResolvesApiWorkspace;

    public function index(Request $request): JsonResponse
    {
        $company = $this->apiCompany();
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $query = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->with(['client:id,name', 'items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->string('client_id')->toString());
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Invoice $inv) => $this->invoicePayload($inv))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $company = $this->apiCompany();
        abort_if((string) $invoice->company_id !== (string) $company->id, 404);
        $invoice->load(['client:id,name', 'items']);

        return response()->json(['data' => $this->invoicePayload($invoice)]);
    }

    public function store(
        Request $request,
        InvoiceTotalsService $totalsService,
        InvoiceReferenceService $referenceService,
        ClientCodeService $clientCodes,
    ): JsonResponse {
        $company = $this->apiCompany();

        $data = $request->validate([
            'client_id' => ['nullable', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'client' => ['nullable', 'array'],
            'client.name' => ['required_with:client', 'string', 'max:255'],
            'client.email' => ['nullable', 'string', 'email', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency)],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::enum(InvoiceStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'customer_notes' => ['nullable', 'string', 'max:10000'],
            'project_id' => ['nullable', 'string', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
        ]);

        if (empty($data['client_id']) && empty($data['client'])) {
            return response()->json(['message' => __('Provide client_id or a client object.')], 422);
        }

        $subtotal = 0;
        foreach ($data['items'] as $row) {
            $subtotal += $row['quantity'] * $row['unit_amount'];
        }
        $taxes = $totalsService->fromSubtotalMinor($subtotal, $company);

        $invoice = DB::transaction(function () use ($data, $company, $taxes, $referenceService, $clientCodes) {
            $clientId = $data['client_id'] ?? null;
            if ($clientId === null && is_array($data['client'] ?? null)) {
                $c = $data['client'];
                $client = Client::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'name' => $c['name'],
                    'email' => $c['email'] ?? null,
                    'phone' => $c['phone'] ?? null,
                ]);
                $clientCodes->assignIfMissing($client);
                $clientId = $client->id;
            }

            $inv = Invoice::query()->withoutGlobalScope('tenant')->create([
                'company_id' => $company->id,
                'client_id' => $clientId,
                'proposal_id' => null,
                'project_id' => $data['project_id'] ?? null,
                'number' => null,
                'currency' => strtoupper($data['currency']),
                'status' => $data['status'] ?? InvoiceStatus::Draft,
                'due_date' => $data['due_date'] ?? null,
                'subtotal_amount' => $taxes['subtotal'],
                'vat_amount' => $taxes['vat'],
                'fiscal_stamp_amount' => $taxes['stamp'],
                'amount' => $taxes['total'],
                'internal_notes' => $data['internal_notes'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $line = $row['quantity'] * $row['unit_amount'];
                InvoiceItem::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'invoice_id' => $inv->id,
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_amount' => $row['unit_amount'],
                    'total_amount' => $line,
                ]);
            }

            $referenceService->assignNextNumber($inv, $company);

            return $inv;
        });

        $invoice->load(['client:id,name', 'items']);

        return response()->json(['data' => $this->invoicePayload($invoice)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status?->value,
            'currency' => $invoice->currency,
            'client_id' => $invoice->client_id,
            'client_name' => $invoice->client?->name,
            'project_id' => $invoice->project_id,
            'due_date' => $invoice->due_date?->format('Y-m-d'),
            'subtotal_amount' => (int) $invoice->subtotal_amount,
            'vat_amount' => (int) $invoice->vat_amount,
            'fiscal_stamp_amount' => (int) $invoice->fiscal_stamp_amount,
            'amount' => (int) $invoice->amount,
            'internal_notes' => $invoice->internal_notes,
            'customer_notes' => $invoice->customer_notes,
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => (int) $item->quantity,
                'unit_amount' => (int) $item->unit_amount,
                'total_amount' => (int) $item->total_amount,
            ])->values(),
            'created_at' => $invoice->created_at?->toIso8601String(),
        ];
    }
}
