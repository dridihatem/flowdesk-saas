<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoicePdfTemplateLibraryController extends Controller
{
    public function index(): View
    {
        $row = PlatformSetting::query()->first();
        $library = is_array($row?->invoice_pdf_library) ? $row->invoice_pdf_library : [];

        return view('admin.invoice-pdf-templates.index', [
            'library' => $library,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $row = PlatformSetting::query()->first() ?? new PlatformSetting;
        $library = is_array($row->invoice_pdf_library) ? $row->invoice_pdf_library : [];

        $request->merge([
            'totals_grand_bg' => $request->filled('totals_grand_bg') ? $request->input('totals_grand_bg') : null,
            'pay_box_bg' => $request->filled('pay_box_bg') ? $request->input('pay_box_bg') : null,
        ]);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', Rule::notIn(['classic'])],
            'label' => ['required', 'string', 'max:120'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'table_header_bg' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'border_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'muted_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'totals_grand_bg' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pay_box_bg' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'compact_header' => ['sometimes', 'boolean'],
        ]);

        if (isset($library[$data['key']])) {
            return redirect()->back()->withErrors(['key' => __('This key is already used.')])->withInput();
        }

        $entry = [
            'label' => $data['label'],
            'primary_color' => $data['primary_color'],
            'accent_color' => $data['accent_color'],
            'table_header_bg' => $data['table_header_bg'],
            'border_color' => $data['border_color'],
            'text_color' => $data['text_color'],
            'muted_color' => $data['muted_color'],
            'compact_header' => $request->boolean('compact_header', true),
        ];
        if (! empty($data['totals_grand_bg'])) {
            $entry['totals_grand_bg'] = $data['totals_grand_bg'];
        }
        if (! empty($data['pay_box_bg'])) {
            $entry['pay_box_bg'] = $data['pay_box_bg'];
        }

        $library[$data['key']] = $entry;

        $row->invoice_pdf_library = $library;
        $row->save();

        return redirect()->route('admin.invoice-pdf-templates.index')->with('status', __('admin_invoice_pdf_template_saved'));
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        abort_if($key === 'classic', 404);

        $row = PlatformSetting::query()->first();
        abort_if(! $row, 404);

        $library = is_array($row->invoice_pdf_library) ? $row->invoice_pdf_library : [];
        unset($library[$key]);
        $row->invoice_pdf_library = $library;
        $row->save();

        return redirect()->route('admin.invoice-pdf-templates.index')->with('status', __('admin_invoice_pdf_template_removed'));
    }
}
