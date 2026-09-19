<?php

namespace App\AI\Nova\Tools\Document;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectDocument;
use App\Models\ProjectFile;

class SearchDocumentsTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'documents.search'; }
    public function description(): string { return 'Search project documents and files in the company.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'query' => ['type' => 'string'],
            'project_id' => ['type' => 'string'],
            'limit' => ['type' => 'integer', 'default' => 10],
        ]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $q = trim((string) ($arguments['query'] ?? ''));
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));

        $docs = ProjectDocument::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->when(! empty($arguments['project_id']), fn ($b) => $b->where('project_id', $arguments['project_id']))
            ->when($q !== '', fn ($b) => $b->where(function ($x) use ($q): void {
                $x->where('name', 'like', '%'.$q.'%')->orWhere('extracted_text', 'like', '%'.$q.'%');
            }))
            ->limit($limit)
            ->get();

        $files = ProjectFile::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->when(! empty($arguments['project_id']), fn ($b) => $b->where('project_id', $arguments['project_id']))
            ->when($q !== '', fn ($b) => $b->where('original_name', 'like', '%'.$q.'%'))
            ->limit($limit)
            ->get();

        return [
            'documents' => $docs->map(fn (ProjectDocument $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'status' => $d->status,
                'project_id' => $d->project_id,
                'mime_type' => $d->mime_type,
            ])->all(),
            'files' => $files->map(fn (ProjectFile $f) => [
                'id' => $f->id,
                'name' => $f->original_name,
                'project_id' => $f->project_id,
                'mime' => $f->mime,
            ])->all(),
        ];
    }
}
