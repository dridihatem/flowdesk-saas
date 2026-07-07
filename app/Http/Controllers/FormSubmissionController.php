<?php

namespace App\Http\Controllers;

use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Form as LeadForm;
use App\Models\FormSubmission;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormSubmissionController extends Controller
{
    public function index(Request $request, LeadForm $form): View
    {
        $this->authorizeForm($form);

        $submissions = FormSubmission::query()->withoutGlobalScopes()
            ->where('form_id', $form->id)
            ->where('company_id', $form->company_id)
            ->latest()
            ->paginate(20);

        return view('forms.submissions', compact('form', 'submissions'));
    }

    public function convertToProject(Request $request, FormSubmission $submission): RedirectResponse
    {
        $form = $submission->form;
        $this->authorizeForm($form);
        abort_if((string) $submission->form_id !== (string) $form->id, 404);

        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $submission->data ?? [];
        $name = $data['name'] ?? $data['Name'] ?? __('Lead from form');
        $email = $data['email'] ?? $data['Email'] ?? null;

        $client = null;
        if (is_string($email) && $email !== '') {
            $client = Client::query()->withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->id, 'email' => $email],
                ['name' => is_string($name) ? $name : __('New client')],
            );
        } elseif (is_string($name) && $name !== '') {
            $client = Client::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name' => $name,
                'email' => null,
            ]);
        }

        $project = Project::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'client_id' => $client?->id,
            'provider_id' => null,
            'created_by' => $request->user()->id,
            'title' => is_string($name) ? $name : __('Project from submission'),
            'status' => ProjectStatus::Draft,
            'description' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'source' => ProjectSource::FormWebsite,
            'form_submission_id' => $submission->id,
        ]);

        return redirect()->route('projects.show', $project)->with('status', __('Project created from submission.'));
    }

    private function authorizeForm(LeadForm $form): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $form->company_id !== (string) $company->id, 403);
    }
}
