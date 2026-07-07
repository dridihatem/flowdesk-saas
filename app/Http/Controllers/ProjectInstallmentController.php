<?php

namespace App\Http\Controllers;

use App\Enums\ProjectInstallmentPaymentMethod;
use App\Models\Project;
use App\Models\ProjectInstallment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectInstallmentController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectCompany($project);
        if (! $project->isClientPriceConfirmed()) {
            return back()->withErrors([
                'installment' => __('Client must confirm the project price before adding installments.'),
            ]);
        }

        $currency = strtoupper((string) ($project->company?->default_currency ?? 'USD'));

        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::enum(ProjectInstallmentPaymentMethod::class)],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $maxOrder = (int) $project->installments()->max('sort_order');

        ProjectInstallment::query()->create([
            'project_id' => $project->id,
            'sort_order' => $maxOrder + 1,
            'due_date' => $validated['due_date'],
            'amount_minor' => flowdesk_decimal_to_minor((string) $validated['amount'], $currency),
            'payment_method' => $validated['payment_method'],
            'label' => $validated['label'] ?? null,
        ]);

        return back()->with('status', __('Installment saved.'));
    }

    public function update(Request $request, Project $project, ProjectInstallment $installment): RedirectResponse
    {
        $this->authorizeProjectCompany($project);
        if (! $project->isClientPriceConfirmed()) {
            return back()->withErrors([
                'installment' => __('Client must confirm the project price before editing installments.'),
            ]);
        }
        abort_if((string) $installment->project_id !== (string) $project->id, 404);

        $currency = strtoupper((string) ($project->company?->default_currency ?? 'USD'));

        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::enum(ProjectInstallmentPaymentMethod::class)],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $installment->update([
            'due_date' => $validated['due_date'],
            'amount_minor' => flowdesk_decimal_to_minor((string) $validated['amount'], $currency),
            'payment_method' => $validated['payment_method'],
            'label' => $validated['label'] ?? null,
        ]);

        return back()->with('status', __('Installment updated.'));
    }

    public function destroy(Project $project, ProjectInstallment $installment): RedirectResponse
    {
        $this->authorizeProjectCompany($project);
        if (! $project->isClientPriceConfirmed()) {
            return back()->withErrors([
                'installment' => __('Client must confirm the project price before removing installments.'),
            ]);
        }
        abort_if((string) $installment->project_id !== (string) $project->id, 404);
        $installment->delete();

        return back()->with('status', __('Installment removed.'));
    }

    private function authorizeProjectCompany(Project $project): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $project->company_id !== (string) $company->id, 403);
    }
}
