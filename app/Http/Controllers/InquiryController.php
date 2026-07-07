<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $status = $request->string('status')->trim()->toString();
        $query = Inquiry::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with(['client', 'project'])
            ->latest();

        if ($status !== '') {
            $query->where('status', $status);
        }

        $inquiries = $query->paginate(20)->withQueryString();

        return view('inquiries.index', compact('inquiries', 'status'));
    }

    public function create(): View
    {
        $company = auth()->user()->company;
        abort_if(! $company, 403);

        return view('inquiries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'source' => ['nullable', 'string', 'max:64'],
        ]);

        Inquiry::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'subject' => $data['subject'],
            'message' => $data['message'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'status' => InquiryStatus::New,
        ]);

        return redirect()->route('inquiries.index')->with('status', __('Inquiry recorded.'));
    }

    public function show(Inquiry $inquiry): View
    {
        $this->authorizeInquiry($inquiry);
        $inquiry->load(['client', 'project']);

        return view('inquiries.show', compact('inquiry'));
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeInquiry($inquiry);

        $data = $request->validate([
            'status' => ['required', Rule::enum(InquiryStatus::class)],
        ]);

        $inquiry->update(['status' => $data['status']]);

        return redirect()->route('inquiries.show', $inquiry)->with('status', __('Inquiry updated.'));
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeInquiry($inquiry);
        $inquiry->delete();

        return redirect()->route('inquiries.index')->with('status', __('Inquiry removed.'));
    }

    public function convertToProject(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeInquiry($inquiry);
        $company = $request->user()->company;
        abort_if(! $company, 403);

        if ($inquiry->project_id) {
            return redirect()->route('projects.show', $inquiry->project_id)->with('status', __('This inquiry already has a project.'));
        }

        $client = null;
        if ($inquiry->contact_email) {
            $client = Client::query()->withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $company->id, 'email' => $inquiry->contact_email],
                ['name' => $inquiry->contact_name ?: $inquiry->subject],
            );
        } elseif ($inquiry->contact_name) {
            $client = Client::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name' => $inquiry->contact_name,
                'email' => null,
            ]);
        }

        $project = Project::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'client_id' => $client?->id,
            'provider_id' => null,
            'created_by' => $request->user()->id,
            'title' => $inquiry->subject,
            'status' => ProjectStatus::Draft,
            'description' => $inquiry->message,
            'source' => ProjectSource::Inquiry,
        ]);

        $inquiry->update([
            'client_id' => $client?->id,
            'project_id' => $project->id,
            'status' => InquiryStatus::InProgress,
        ]);

        return redirect()->route('projects.show', $project)->with('status', __('Project created from inquiry.'));
    }

    private function authorizeInquiry(Inquiry $inquiry): void
    {
        $company = auth()->user()?->company;
        abort_if(! $company || (string) $inquiry->company_id !== (string) $company->id, 403);
    }
}
