<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Services\InvoicePdfThemeService;
use App\Services\InvoiceReferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceDocumentController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request, InvoicePdfThemeService $pdfThemes): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        $templates = array_merge([
            'invoice_email_intro' => '',
            'invoice_email_footer' => '',
            'invoice_pdf_footer' => '',
            'invoice_reference_prefix' => 'INV',
            'invoice_reference_pad' => 3,
            'invoice_pdf_template' => 'classic',
        ], $company->settings?->document_templates ?? []);

        $referencePreview = app(InvoiceReferenceService::class)->examplePreview($company);

        $library = $pdfThemes->libraryPresets();
        $templateOptions = ['classic' => __('invoice_pdf_template_classic')];
        foreach ($library as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            $templateOptions[(string) $key] = (string) ($row['label'] ?? $key);
        }

        return view('settings.invoice-documents', compact('templates', 'referencePreview', 'templateOptions'));
    }

    public function update(Request $request, InvoicePdfThemeService $pdfThemes): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $allowedTemplates = array_merge(['classic'], array_keys($pdfThemes->libraryPresets()));

        $data = $request->validate([
            'invoice_email_intro' => ['nullable', 'string', 'max:5000'],
            'invoice_email_footer' => ['nullable', 'string', 'max:5000'],
            'invoice_pdf_footer' => ['nullable', 'string', 'max:5000'],
            'invoice_reference_prefix' => ['required', 'string', 'max:32'],
            'invoice_reference_pad' => ['required', 'integer', 'min:1', 'max:12'],
            'invoice_pdf_template' => ['required', 'string', Rule::in($allowedTemplates)],
        ]);

        $settings = $company->settings()->firstOrCreate();
        $prev = $settings->document_templates ?? [];
        $prefix = trim((string) $data['invoice_reference_prefix']);
        if ($prefix === '') {
            $prefix = 'INV';
        }
        $settings->update([
            'document_templates' => array_merge($prev, [
                'invoice_email_intro' => $data['invoice_email_intro'] ?? ($prev['invoice_email_intro'] ?? ''),
                'invoice_email_footer' => $data['invoice_email_footer'] ?? ($prev['invoice_email_footer'] ?? ''),
                'invoice_pdf_footer' => $data['invoice_pdf_footer'] ?? ($prev['invoice_pdf_footer'] ?? ''),
                'invoice_reference_prefix' => $prefix,
                'invoice_reference_pad' => (int) $data['invoice_reference_pad'],
                'invoice_pdf_template' => $data['invoice_pdf_template'],
            ]),
        ]);

        return redirect()->route('settings.invoice-documents')->with('status', __('Invoice document settings saved.'));
    }
}
