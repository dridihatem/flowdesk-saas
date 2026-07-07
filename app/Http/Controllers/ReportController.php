<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Services\AiCreditUsageService;
use App\Services\AnalyticsService;
use App\Services\DashboardMetricsService;
use App\Services\InvoicePdfService;
use App\Services\PlanLimitService;
use App\Services\PlatformLlmRouter;
use App\Services\ReportAiService;
use App\Services\WorkspaceAiConfigService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const MAX_INVOICES_PDF_ZIP = 1000;

    public function index(
        Request $request,
        DashboardMetricsService $metrics,
        AnalyticsService $analytics,
        PlanLimitService $planLimits,
    ): View {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        [$from, $to] = $this->resolveDateRange($request);

        $kpis = $metrics->forCompany($company);
        $commission = $analytics->providerCommissionSummary($company);
        $projectSources = $analytics->projectSourcesReport($company);

        $invoicesInRange = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->with('client')
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $projectsInRange = Project::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('client')
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $invoiceTotalsByCurrency = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('upper(currency) as currency, COALESCE(SUM(amount), 0) as total_minor, COUNT(*) as invoice_count')
            ->groupBy('currency')
            ->orderByDesc('total_minor')
            ->get()
            ->map(fn ($row): array => [
                'currency' => strtoupper((string) $row->currency),
                'total_minor' => (int) $row->total_minor,
                'count' => (int) $row->invoice_count,
            ])
            ->values()
            ->all();

        $defaultCurrency = strtoupper((string) ($company->default_currency ?? 'USD'));
        $defaultInvoiceRow = collect($invoiceTotalsByCurrency)->firstWhere('currency', $defaultCurrency);
        $invoiceTotalMinor = (int) ($defaultInvoiceRow['total_minor'] ?? collect($invoiceTotalsByCurrency)->sum('total_minor'));

        $paymentsForRange = Payment::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('created_at', [$from, $to])
            ->with('invoice')
            ->orderByDesc('created_at')
            ->get();

        $paymentsInRange = $paymentsForRange->take(200)->values();

        $channelTotals = [];
        foreach ($paymentsForRange as $p) {
            $raw = $p->provider !== null ? trim((string) $p->provider) : '';
            $key = $raw !== '' ? strtolower($raw) : 'other';
            if (! isset($channelTotals[$key])) {
                $channelTotals[$key] = ['count' => 0, 'amount_minor' => 0];
            }
            $channelTotals[$key]['count']++;
            $channelTotals[$key]['amount_minor'] += (int) $p->amount;
        }
        uasort($channelTotals, fn ($a, $b) => $b['amount_minor'] <=> $a['amount_minor']);

        $paymentsTotalMinorInRange = (int) $paymentsForRange->sum('amount');

        $aiCounselCost = app(AiCreditUsageService::class)->creditsForTask(AiCreditUsageService::TASK_REPORT_COUNSEL);
        $aiCounselAvailable = $planLimits->isFeatureEnabled($company, 'ai_credits')
            && $planLimits->allows($company, 'ai_credits', $aiCounselCost);

        return view('reports.index', compact(
            'company',
            'from',
            'to',
            'kpis',
            'commission',
            'projectSources',
            'invoicesInRange',
            'projectsInRange',
            'invoiceTotalMinor',
            'invoiceTotalsByCurrency',
            'paymentsInRange',
            'paymentsForRange',
            'channelTotals',
            'paymentsTotalMinorInRange',
            'aiCounselAvailable',
            'aiCounselCost',
        ));
    }

    public function export(Request $request): Response|RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $type = $request->query('type', 'invoices');
        if (! in_array($type, ['invoices', 'projects', 'invoices-pdf'], true)) {
            abort(404);
        }

        [$from, $to] = $this->resolveDateRange($request);

        return match ($type) {
            'invoices' => $this->streamInvoicesCsv($company, $from, $to),
            'invoices-pdf' => $this->streamInvoicesPdfZip($company, $from, $to, $request),
            default => $this->streamProjectsCsv($company, $from, $to),
        };
    }

    public function aiCounsel(
        Request $request,
        ReportAiService $reportAi,
        PlanLimitService $planLimits,
        PlatformLlmRouter $llm,
        AiCreditUsageService $usage,
    ): JsonResponse {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_REPORT_COUNSEL);
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        if (! $llm->isAvailable($company)) {
            return response()->json([
                'message' => app(WorkspaceAiConfigService::class)->unavailableMessage($company),
            ], 503);
        }

        [$from, $to] = $this->resolveDateRange($request);
        $context = $reportAi->buildCounselContext($company, $from, $to);

        try {
            $result = $llm->suggest('report_counsel', $context, $company);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        $credits = $usage->recordForTask($company, AiCreditUsageService::TASK_REPORT_COUNSEL);

        return response()->json([
            'suggestion' => $result['suggestion'],
            'model' => $result['model'],
            'disclaimer' => __('AI-generated content — review before sharing with clients.'),
            'ai_credits_charged' => $credits,
            'ai_credits_cost' => $creditCost,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]);
    }

    public function exportCounselPdf(Request $request): Response
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        app(PlanLimitService::class)->assertAllows($company, 'ai_credits');

        $data = $request->validate([
            'counsel' => ['required', 'string', 'max:50000'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $request->date('from')?->format('Y-m-d') ?? now()->subDays(30)->format('Y-m-d');
        $to = $request->date('to')?->format('Y-m-d') ?? now()->format('Y-m-d');

        $filename = 'report-counsel-'.Str::slug($company->name).'-'.$from.'-to-'.$to.'.pdf';

        return Pdf::loadView('reports.counsel-pdf', [
            'companyName' => $company->name,
            'from' => $from,
            'to' => $to,
            'counsel' => $data['counsel'],
        ])->download($filename);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $from = ($request->date('from') ?? Carbon::now()->subDays(30))->copy()->startOfDay();
        $to = ($request->date('to') ?? Carbon::now())->copy()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function exportRedirect(Request $request, Carbon $from, Carbon $to, string $message): RedirectResponse
    {
        return redirect()
            ->route('reports.index', [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ])
            ->withErrors(['export' => $message]);
    }

    private function streamInvoicesCsv(Company $company, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'invoices-'.Str::slug($company->name).'-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($company, $from, $to): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                __('Invoice #'),
                __('Client'),
                __('Status'),
                __('Amount (minor)'),
                __('Currency'),
                __('Due date'),
                __('Created at'),
            ]);

            Invoice::query()->withoutGlobalScope('tenant')
                ->where('company_id', $company->id)
                ->with('client')
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $inv) {
                        fputcsv($out, [
                            $inv->number ?? $inv->id,
                            $inv->client?->name ?? '',
                            $inv->status->value,
                            (string) $inv->amount,
                            $inv->currency,
                            $inv->due_date?->format('Y-m-d') ?? '',
                            $inv->created_at?->format('c') ?? '',
                        ]);
                    }
                }
                );

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function streamInvoicesPdfZip(Company $company, Carbon $from, Carbon $to, Request $request): BinaryFileResponse|RedirectResponse
    {
        $pdfService = app(InvoicePdfService::class);

        $total = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        if ($total === 0) {
            return $this->exportRedirect($request, $from, $to, __('No invoices in this date range.'));
        }

        if ($total > self::MAX_INVOICES_PDF_ZIP) {
            return $this->exportRedirect(
                $request,
                $from,
                $to,
                __('Too many invoices for one ZIP export. Narrow the date range (maximum :max invoices).', ['max' => self::MAX_INVOICES_PDF_ZIP])
            );
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('invzip_', true).'.zip';
        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, __('Could not create export archive.'));
        }

        $added = 0;
        $failed = 0;

        Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->chunk(50, function ($rows) use ($zip, $pdfService, &$added, &$failed): void {
                foreach ($rows as $inv) {
                    try {
                        $zip->addFromString($pdfService->zipEntryName($inv), $pdfService->output($inv));
                        $added++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('reports.export.invoice_pdf_failed', [
                            'invoice_id' => $inv->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $zip->close();

        if ($added === 0) {
            @unlink($path);

            $message = $failed > 0
                ? __('Could not generate invoice PDFs for this range. Check invoice templates and try again.')
                : __('No invoices in this date range.');

            return $this->exportRedirect($request, $from, $to, $message);
        }

        $filename = 'invoices-pdf-'.Str::slug($company->name).'-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.zip';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function streamProjectsCsv(Company $company, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'projects-'.Str::slug($company->name).'-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($company, $from, $to): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                __('Title'),
                __('Status'),
                __('Source'),
                __('Client'),
                __('Deadline'),
                __('Created at'),
            ]);

            Project::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->with('client')
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $p) {
                        fputcsv($out, [
                            $p->title,
                            $p->status->value,
                            $p->source->value,
                            $p->client?->name ?? '',
                            $p->final_deadline?->format('Y-m-d') ?? '',
                            $p->created_at?->format('c') ?? '',
                        ]);
                    }
                }
                );

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
