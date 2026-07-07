<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFileShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFileVaultController extends Controller
{
    public function download(Project $project, string $file): StreamedResponse
    {
        $this->authorizeVault($project);
        $fileModel = $this->vaultFile($project, $file);

        return Storage::disk($fileModel->disk)->download($fileModel->path, $fileModel->original_name);
    }

    public function storeShare(Request $request, Project $project, string $file): RedirectResponse
    {
        $this->authorizeVault($project);
        $fileModel = $this->vaultFile($project, $file);

        $data = $request->validate([
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
            'expires_in' => ['nullable', 'integer', 'in:0,1,7,30'],
        ]);

        $days = (int) ($data['expires_in'] ?? 0);

        ProjectFileShare::query()->create([
            'company_id' => $project->company_id,
            'project_file_id' => $fileModel->id,
            'token' => Str::random(48),
            'password_hash' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
            'expires_at' => $days > 0 ? now()->addDays($days) : null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', __('project_vault_share_created'));
    }

    public function destroyShare(Project $project, string $file, string $share): RedirectResponse
    {
        $this->authorizeVault($project);
        $fileModel = $this->vaultFile($project, $file);

        ProjectFileShare::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('project_file_id', $fileModel->id)
            ->findOrFail($share)
            ->delete();

        return back()->with('status', __('project_vault_share_revoked'));
    }

    private function vaultFile(Project $project, string $fileId): ProjectFile
    {
        return ProjectFile::query()
            ->withoutGlobalScopes()
            ->where('company_id', $project->company_id)
            ->where('project_id', $project->id)
            ->where('is_vault', true)
            ->findOrFail($fileId);
    }

    private function authorizeVault(Project $project): void
    {
        $user = auth()->user();
        $company = $user?->company;

        abort_if(! $company || (string) $project->company_id !== (string) $company->id, 403);
        abort_unless($user->can('workspace.access_vault'), 403);
    }
}
