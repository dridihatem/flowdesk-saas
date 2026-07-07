<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Provider;
use App\Services\InvoiceTotalsService;
use App\Services\ProposalMailService;
use App\Services\ProposalPdfService;
use App\Services\ProposalReferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

            return $next($request);
        })->only(['create', 'store', 'edit', 'update', 'destroy', 'send', 'accept']);
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $proposals = Proposal::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with(['client', 'project', 'provider'])
            ->latest()
            ->paginate(20);

        return view('proposals.index', compact('proposals'));
    }

    public function create(Request $request, InvoiceTotalsService $totalsService): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        return view('proposals.create', $this->formContext($company, null, $request));
    }

    public function store(
        Request $request,
        InvoiceTotalsService $totalsService,
        ProposalReferenceService $referenceService,
        ProposalMailService $mailer,
    ): RedirectResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $this->validatedDocument($request, $company);
        $taxes = $this->taxesFromItems($data['items'], $company, $totalsService);
        $client = $this->resolveClient($company, $data);

        $proposal = DB::transaction(function () use ($data, $company, $client, $taxes, $referenceService) {
            $proposal = Proposal::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'project_id' => $data['project_id'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'name' => $data['name'],
                'number' => null,
                'status' => ProposalStatus::Draft,
                'subtotal_amount' => $taxes['subtotal'],
                'vat_amount' => $taxes['vat'],
                'fiscal_stamp_amount' => $taxes['stamp'],
                'amount' => $taxes['total'],
                'currency' => strtoupper($data['currency']),
                'valid_until' => $data['valid_until'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $this->syncItems($proposal, $company, $data['items']);
            $referenceService->assignNextNumber($proposal, $company);

            return $proposal;
        });

        if ($request->boolean('send_to_client')) {
            return $this->deliverToClient($proposal, $company, $mailer, __('Quote saved and sent to client.'));
        }

        return redirect()->route('proposals.show', $proposal)->with('status', __('Quote saved.'));
    }

    public function show(Proposal $proposal): View
    {
        $this->authorizeProposal($proposal);
        $proposal->load([
            'company',
            'client',
            'project',
            'provider',
            'items',
            'invoices',
            'negotiations' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $currencyOptions = flowdesk_currency_select_options(
            $proposal->company?->default_currency,
            $proposal->currency
        );

        return view('proposals.show', compact('proposal', 'currencyOptions'));
    }

    public function edit(Proposal $proposal): View
    {
        $this->authorizeProposal($proposal);
        abort_if(in_array($proposal->status, [ProposalStatus::Accepted, ProposalStatus::Rejected], true), 403, __('Cannot edit a quote that is already accepted or rejected.'));

        $company = auth()->user()->company;
        abort_if(! $company, 403);
        $proposal->load('items');

        return view('proposals.edit', array_merge(
            $this->formContext($company, $proposal),
            compact('proposal'),
        ));
    }

    public function update(
        Request $request,
        Proposal $proposal,
        InvoiceTotalsService $totalsService,
        ProposalMailService $mailer,
    ): RedirectResponse {
        $this->authorizeProposal($proposal);
        abort_if(in_array($proposal->status, [ProposalStatus::Accepted, ProposalStatus::Rejected], true), 403);

        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $this->validatedDocument($request, $company, $proposal);
        $taxes = $this->taxesFromItems($data['items'], $company, $totalsService);
        $client = $this->resolveClient($company, $data);

        $proposal->update([
            'client_id' => $client->id,
            'project_id' => $data['project_id'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'name' => $data['name'],
            'subtotal_amount' => $taxes['subtotal'],
            'vat_amount' => $taxes['vat'],
            'fiscal_stamp_amount' => $taxes['stamp'],
            'amount' => $taxes['total'],
            'currency' => strtoupper($data['currency']),
            'valid_until' => $data['valid_until'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
        ]);

        $this->syncItems($proposal, $company, $data['items']);

        if ($request->boolean('send_to_client')) {
            return $this->deliverToClient($proposal->fresh(), $company, $mailer, __('Quote updated and sent to client.'));
        }

        return redirect()->route('proposals.show', $proposal)->with('status', __('Quote updated.'));
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->authorizeProposal($proposal);
        abort_if($proposal->invoices()->exists(), 403, __('Cannot delete a quote that has invoices.'));

        $proposal->delete();

        return redirect()->route('proposals.index')->with('status', __('Quote deleted.'));
    }

    public function pdf(Proposal $proposal, ProposalPdfService $pdfs): StreamedResponse
    {
        $this->authorizeProposal($proposal);

        $filename = 'quote-'.($proposal->number ?? $proposal->id).'.pdf';

        return response()->streamDownload(function () use ($proposal, $pdfs): void {
            echo $pdfs->output($proposal);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function send(Request $request, Proposal $proposal, ProposalMailService $mailer): RedirectResponse
    {
        $this->authorizeProposal($proposal);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        return $this->deliverToClient($proposal, $company, $mailer, __('Quote email queued for delivery.'));
    }

    public function accept(Proposal $proposal): RedirectResponse
    {
        $this->authorizeProposal($proposal);

        if ($proposal->status === ProposalStatus::Accepted) {
            return redirect()->route('proposals.show', $proposal)->with('status', __('Quote is already accepted.'));
        }

        if ($proposal->status === ProposalStatus::Rejected) {
            return redirect()->route('proposals.show', $proposal)->withErrors(['accept' => __('Cannot accept a rejected quote.')]);
        }

        $proposal->update(['status' => ProposalStatus::Accepted]);

        return redirect()->route('proposals.show', $proposal)->with('status', __('Quote marked as accepted. You can convert it to an invoice.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formContext(Company $company, ?Proposal $proposal = null, ?Request $request = null): array
    {
        $company->loadMissing('settings');
        $billing = is_array($company->settings?->billing) ? $company->settings->billing : [];
        $currency = strtoupper((string) old('currency', $proposal?->currency ?? $company->default_currency ?? 'USD'));
        $taxPreview = [
            'vat_percent' => (float) ($billing['vat_percent'] ?? 0),
            'fiscal_stamp_enabled' => filter_var($billing['fiscal_stamp_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'fiscal_stamp_minor' => (int) ($billing['fiscal_stamp_minor'] ?? 0),
            'invoice_currency' => $currency,
        ];

        $prefillClientId = old('client_id', $request?->query('client') ?? $proposal?->client_id ?? '');
        $assistantPrefill = $request?->session()->pull('assistant_proposal_prefill');

        if (is_array($assistantPrefill)) {
            $prefillClientId = old('client_id', $assistantPrefill['client_id'] ?? $prefillClientId);
        }

        return [
            'projects' => Project::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->orderBy('title')
                ->get(),
            'clients' => Client::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get(),
            'providers' => Provider::query()->withoutGlobalScopes()->where('company_id', $company->id)->orderBy('name')->get(),
            'currencyOptions' => flowdesk_currency_select_options($company->default_currency, $proposal?->currency),
            'taxPreview' => $taxPreview,
            'referencePreview' => app(ProposalReferenceService::class)->examplePreview($company),
            'prefillClientId' => $prefillClientId,
            'assistantPrefill' => is_array($assistantPrefill) ? $assistantPrefill : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDocument(Request $request, Company $company, ?Proposal $forProposal = null): array
    {
        $companyId = $company->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'project_id' => ['nullable', 'string', Rule::exists('projects', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'provider_id' => ['nullable', 'string', Rule::exists('providers', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'currency' => ['required', 'string', 'size:3', flowdesk_currency_rule($company->default_currency, $forProposal?->currency)],
            'valid_until' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'customer_notes' => ['nullable', 'string', 'max:10000'],
            'send_to_client' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function resolveClient(Company $company, array $data): Client
    {
        $client = Client::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('id', $data['client_id'])
            ->first();
        abort_if(! $client, 422);

        return $client;
    }

    /**
     * @param  list<array{description: string, quantity: int, unit_amount: int}>  $items
     * @return array{subtotal: int, vat: int, stamp: int, total: int}
     */
    private function taxesFromItems(array $items, Company $company, InvoiceTotalsService $totalsService): array
    {
        $subtotal = 0;
        foreach ($items as $row) {
            $subtotal += $row['quantity'] * $row['unit_amount'];
        }

        return $totalsService->fromSubtotalMinor($subtotal, $company);
    }

    /**
     * @param  list<array{description: string, quantity: int, unit_amount: int}>  $items
     */
    private function syncItems(Proposal $proposal, Company $company, array $items): void
    {
        $proposal->items()->delete();
        foreach ($items as $row) {
            $line = $row['quantity'] * $row['unit_amount'];
            ProposalItem::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'proposal_id' => $proposal->id,
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'unit_amount' => $row['unit_amount'],
                'total_amount' => $line,
            ]);
        }
    }

    private function deliverToClient(Proposal $proposal, Company $company, ProposalMailService $mailer, string $successMessage): RedirectResponse
    {
        try {
            $mailer->send($proposal->loadMissing(['client', 'items']), $company);
            $proposal->update([
                'status' => ProposalStatus::Sent,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('proposals.show', $proposal)->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('proposals.show', $proposal)->with('status', $successMessage);
    }

    private function authorizeProposal(Proposal $proposal): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $proposal->company_id !== (string) $company->id, 403);
    }
}
