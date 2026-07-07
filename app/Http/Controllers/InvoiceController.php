<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ClientCodeService;
use App\Services\InvoiceMailService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceReferenceService;
use App\Services\InvoiceTotalsService;
use App\Services\ProjectInvoiceLinesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member', 'business_provider']), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $query = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->with(['client', 'proposal'])
            ->withSum(['payments' => fn ($q) => $q->where('status', PaymentStatus::Completed)], 'amount');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->query('client_id'));
        }
        $settlement = $request->string('settlement')->trim()->toString();
        if ($settlement !== '' && $settlement !== 'all') {
            $query->settlement($settlement);
        }
        $qSearch = $request->string('q')->trim()->toString();
        if ($qSearch !== '') {
            $query->where(function ($sub) use ($qSearch): void {
                $sub->where('number', 'like', '%'.$qSearch.'%')
                    ->orWhereHas('client', fn ($cq) => $cq->where('name', 'like', '%'.$qSearch.'%'));
            });
        }

        $invoices = $query->latest()->paginate(20)->withQueryString();

        $clients = Client::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        $initialPreviewPanelUrl = null;
        if ($request->filled('preview')) {
            $previewRow = Invoice::query()->withoutGlobalScope('tenant')
                ->where('company_id', $company->id)
                ->whereKey($request->query('preview'))
                ->first();
            if ($previewRow) {
                $initialPreviewPanelUrl = route('invoices.preview-panel', $previewRow);
            }
        }

        return view('invoices.index', compact('invoices', 'initialPreviewPanelUrl', 'clients'));
    }

    public function previewPanel(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        $invoice->loadMissing(['client', 'items']);
        $canManageInvoices = auth()->user()->hasAnyRole(['company_admin', 'team_member']);

        return view('invoices.partials.preview-slide-panel', compact('invoice', 'canManageInvoices'));
    }

    public function create(Request $request, ProjectInvoiceLinesService $projectInvoiceLines): View
    {
        $company = auth()->user()->company;
        abort_if(! $company, 403);
        abort_if(auth()->user()->hasRole('business_provider'), 403);
        $clients = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get();
        $currencyOptions = flowdesk_currency_select_options($company->default_currency);
        $company->loadMissing('settings');
        $billing = is_array($company->settings?->billing) ? $company->settings->billing : [];
        $invoiceCurrency = strtoupper((string) old('currency', $company->default_currency ?? 'USD'));
        $taxPreview = [
            'vat_percent' => (float) ($billing['vat_percent'] ?? 0),
            'fiscal_stamp_enabled' => filter_var($billing['fiscal_stamp_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'fiscal_stamp_minor' => (int) ($billing['fiscal_stamp_minor'] ?? 0),
            'invoice_currency' => $invoiceCurrency,
        ];

        $invoiceProjectPrefill = null;
        if ($request->filled('project')) {
            $proj = Project::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->with('tasks')
                ->whereKey($request->query('project'))
                ->first();
            if ($proj) {
                $lines = $projectInvoiceLines->suggestedLines($proj);
                $invoiceProjectPrefill = [
                    'project_id' => $proj->id,
                    'client_id' => $proj->client_id,
                    'items' => array_map(static fn (array $l): array => [
                        'description' => $l['description'],
                        'quantity' => $l['quantity'],
                        'unit_amount' => $l['unit_amount'],
                    ], $lines),
                ];
            }
        }

        $prefillClientId = old('client_id', $request->query('client'), ($invoiceProjectPrefill ?? [])['client_id'] ?? '');

        return view('invoices.create', compact('clients', 'currencyOptions', 'taxPreview', 'invoiceProjectPrefill', 'prefillClientId'));
    }

    public function store(Request $request, InvoiceTotalsService $totalsService, InvoiceReferenceService $referenceService): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if($request->user()->hasRole('business_provider'), 403);

        $data = $request->validate([
            'client_mode' => ['required', Rule::in(['pick', 'new'])],
            'client_id' => ['nullable', 'string', 'exists:clients,id'],
            'new_client_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'new_client_email' => ['nullable', 'string', 'email', 'max:255'],
            'new_client_phone' => ['nullable', 'string', 'max:64'],
            'create_client_account' => ['sometimes', 'boolean'],
            'new_client_password' => ['nullable', 'string', 'min:8', 'confirmed', Rule::requiredIf($request->boolean('create_client_account'))],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency)],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'customer_notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
            'project_id' => ['nullable', 'string', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('company_id', $company->id))],
        ]);

        if ($data['client_mode'] === 'pick' && ! empty($data['client_id'])) {
            $c = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->where('id', $data['client_id'])->first();
            abort_if(! $c, 422);
        }

        if ($data['client_mode'] === 'new' && $request->boolean('create_client_account')) {
            if (empty($data['new_client_email'])) {
                throw ValidationException::withMessages([
                    'new_client_email' => __('An email is required to create a client login.'),
                ]);
            }
            if (User::query()->where('email', $data['new_client_email'])->exists()) {
                throw ValidationException::withMessages([
                    'new_client_email' => __('This email is already registered.'),
                ]);
            }
        }

        $subtotal = 0;
        foreach ($data['items'] as $row) {
            $subtotal += $row['quantity'] * $row['unit_amount'];
        }
        $taxes = $totalsService->fromSubtotalMinor($subtotal, $company);

        $invoice = DB::transaction(function () use ($data, $company, $request, $taxes, $referenceService) {
            $clientId = null;
            if ($data['client_mode'] === 'new') {
                $client = Client::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'name' => $data['new_client_name'],
                    'email' => $data['new_client_email'] ?? null,
                    'phone' => $data['new_client_phone'] ?? null,
                ]);
                if ($request->boolean('create_client_account')) {
                    $user = User::query()->create([
                        'name' => $data['new_client_name'],
                        'email' => $data['new_client_email'],
                        'password' => Hash::make((string) $data['new_client_password']),
                        'company_id' => $company->id,
                        'email_verified_at' => now(),
                    ]);
                    $user->assignRole('client');
                    $client->update(['user_id' => $user->id]);
                }
                app(ClientCodeService::class)->assignIfMissing($client);
                $clientId = $client->id;
            } else {
                $clientId = $data['client_id'] ?? null;
            }

            $inv = Invoice::query()->withoutGlobalScope('tenant')->create([
                'company_id' => $company->id,
                'client_id' => $clientId,
                'proposal_id' => null,
                'project_id' => $data['project_id'] ?? null,
                'number' => null,
                'status' => $data['status'],
                'subtotal_amount' => $taxes['subtotal'],
                'vat_amount' => $taxes['vat'],
                'fiscal_stamp_amount' => $taxes['stamp'],
                'amount' => $taxes['total'],
                'currency' => strtoupper($data['currency']),
                'due_date' => $data['due_date'] ?? null,
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

        return redirect()->route('invoices.show', $invoice)->with('status', __('Invoice created.'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        $invoice->load([
            'client',
            'proposal',
            'items',
            'payments',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);
        abort_if(auth()->user()->hasRole('business_provider'), 403);
        $company = auth()->user()->company;
        $clients = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get();
        $invoice->load('items');
        $currencyOptions = flowdesk_currency_select_options($company->default_currency, $invoice->currency);
        $company->loadMissing('settings');
        $billing = is_array($company->settings?->billing) ? $company->settings->billing : [];
        $invoiceCurrency = strtoupper((string) old('currency', $invoice->currency));
        $taxPreview = [
            'vat_percent' => (float) ($billing['vat_percent'] ?? 0),
            'fiscal_stamp_enabled' => filter_var($billing['fiscal_stamp_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'fiscal_stamp_minor' => (int) ($billing['fiscal_stamp_minor'] ?? 0),
            'invoice_currency' => $invoiceCurrency,
        ];
        $referencePreview = app(InvoiceReferenceService::class)->examplePreview($company);

        return view('invoices.edit', compact('invoice', 'clients', 'currencyOptions', 'taxPreview', 'referencePreview'));
    }

    public function update(Request $request, Invoice $invoice, InvoiceTotalsService $totalsService): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        abort_if($request->user()->hasRole('business_provider'), 403);

        $company = $request->user()->company;
        abort_if(! $company, 403);
        $data = $request->validate([
            'number' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('invoices', 'number')->where(fn ($q) => $q->where('company_id', $company->id))->ignore($invoice->id),
            ],
            'client_id' => ['nullable', 'string', 'exists:clients,id'],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency, $invoice->currency)],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'customer_notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
        ]);
        if (! empty($data['client_id'])) {
            $c = Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->where('id', $data['client_id'])->first();
            abort_if(! $c, 422);
        }

        $subtotal = 0;
        foreach ($data['items'] as $row) {
            $subtotal += $row['quantity'] * $row['unit_amount'];
        }
        $taxes = $totalsService->fromSubtotalMinor($subtotal, $company);

        $number = array_key_exists('number', $data)
            ? (trim((string) $data['number']) !== '' ? trim((string) $data['number']) : null)
            : $invoice->number;

        $invoice->update([
            'number' => $number,
            'client_id' => $data['client_id'] ?? null,
            'status' => $data['status'],
            'subtotal_amount' => $taxes['subtotal'],
            'vat_amount' => $taxes['vat'],
            'fiscal_stamp_amount' => $taxes['stamp'],
            'amount' => $taxes['total'],
            'currency' => strtoupper($data['currency']),
            'due_date' => $data['due_date'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
        ]);

        $invoice->items()->delete();
        foreach ($data['items'] as $row) {
            $line = $row['quantity'] * $row['unit_amount'];
            InvoiceItem::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'invoice_id' => $invoice->id,
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'unit_amount' => $row['unit_amount'],
                'total_amount' => $line,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)->with('status', __('Invoice updated.'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        abort_if(auth()->user()->hasRole('business_provider'), 403);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', __('Invoice deleted.'));
    }

    public function duplicate(Invoice $invoice, InvoiceTotalsService $totalsService, InvoiceReferenceService $referenceService): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        abort_if(auth()->user()->hasRole('business_provider'), 403);
        $company = auth()->user()->company;
        abort_if(! $company, 403);

        $invoice->load('items');
        $subtotal = (int) $invoice->items->sum(fn ($i) => $i->total_amount);
        $taxes = $totalsService->fromSubtotalMinor($subtotal, $company);

        $new = DB::transaction(function () use ($invoice, $company, $taxes, $referenceService) {
            $inv = Invoice::query()->withoutGlobalScope('tenant')->create([
                'company_id' => $company->id,
                'client_id' => $invoice->client_id,
                'proposal_id' => null,
                'project_id' => $invoice->project_id,
                'number' => null,
                'status' => InvoiceStatus::Draft,
                'subtotal_amount' => $taxes['subtotal'],
                'vat_amount' => $taxes['vat'],
                'fiscal_stamp_amount' => $taxes['stamp'],
                'amount' => $taxes['total'],
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date,
                'internal_notes' => $invoice->internal_notes,
                'customer_notes' => $invoice->customer_notes,
            ]);

            foreach ($invoice->items as $row) {
                InvoiceItem::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'invoice_id' => $inv->id,
                    'description' => $row->description,
                    'quantity' => $row->quantity,
                    'unit_amount' => $row->unit_amount,
                    'total_amount' => $row->total_amount,
                ]);
            }

            $referenceService->assignNextNumber($inv, $company);

            return $inv;
        });

        return redirect()->route('invoices.show', $new)->with('status', __('Invoice duplicated.'));
    }

    public function pdf(Invoice $invoice, InvoicePdfService $pdfs): StreamedResponse
    {
        $this->authorizeInvoice($invoice);

        $filename = 'invoice-'.($invoice->number ?? $invoice->id).'.pdf';

        return response()->streamDownload(function () use ($invoice, $pdfs): void {
            echo $pdfs->output($invoice);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function send(Request $request, Invoice $invoice, InvoiceMailService $mailer): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        try {
            $mailer->send($invoice, $company);
            if ($invoice->status === InvoiceStatus::Draft) {
                $invoice->update(['status' => InvoiceStatus::Sent]);
            }
        } catch (\Throwable $e) {
            return redirect()->route('invoices.show', $invoice)->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('invoices.show', $invoice)->with('status', __('Invoice email queued for delivery.'));
    }

    public function fromProposal(Request $request, Proposal $proposal, InvoiceTotalsService $totalsService, InvoiceReferenceService $referenceService): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company || (string) $proposal->company_id !== (string) $company->id, 403);

        if ($proposal->status !== ProposalStatus::Accepted) {
            return redirect()->route('proposals.show', $proposal)->withErrors(['convert' => __('Only accepted quotes can be converted to an invoice.')]);
        }

        $existing = Invoice::query()->withoutGlobalScope('tenant')->where('proposal_id', $proposal->id)->first();
        if ($existing) {
            return redirect()->route('invoices.show', $existing)->with('status', __('Invoice already exists for this quote.'));
        }

        $proposal->load('items');
        $subtotal = (int) $proposal->subtotal_amount;
        if ($proposal->items->isNotEmpty()) {
            $subtotal = (int) $proposal->items->sum(fn ($i) => $i->total_amount);
        }
        $taxes = $totalsService->fromSubtotalMinor($subtotal, $company);
        if ($proposal->subtotal_amount > 0) {
            $taxes = [
                'subtotal' => (int) $proposal->subtotal_amount,
                'vat' => (int) $proposal->vat_amount,
                'stamp' => (int) $proposal->fiscal_stamp_amount,
                'total' => (int) $proposal->amount,
            ];
        }

        $invoice = Invoice::query()->withoutGlobalScope('tenant')->create([
            'company_id' => $company->id,
            'client_id' => $proposal->client_id,
            'proposal_id' => $proposal->id,
            'project_id' => $proposal->project_id,
            'number' => null,
            'status' => InvoiceStatus::Draft,
            'subtotal_amount' => $taxes['subtotal'],
            'vat_amount' => $taxes['vat'],
            'fiscal_stamp_amount' => $taxes['stamp'],
            'amount' => $taxes['total'],
            'currency' => $proposal->currency,
            'due_date' => now()->addDays(30),
            'customer_notes' => $proposal->customer_notes,
        ]);

        $referenceService->assignNextNumber($invoice, $company);

        if ($proposal->items->isNotEmpty()) {
            foreach ($proposal->items as $row) {
                InvoiceItem::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'description' => $row->description,
                    'quantity' => $row->quantity,
                    'unit_amount' => $row->unit_amount,
                    'total_amount' => $row->total_amount,
                ]);
            }
        } else {
            InvoiceItem::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'invoice_id' => $invoice->id,
                'description' => $proposal->name,
                'quantity' => 1,
                'unit_amount' => $proposal->amount,
                'total_amount' => $proposal->amount,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)->with('status', __('Invoice created from quote.'));
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $invoice->company_id !== (string) $company->id, 403);
    }
}
