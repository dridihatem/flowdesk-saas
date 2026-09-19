<?php

namespace App\AI\Nova\Tools\Document;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectDocument;

class GetDocumentTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'documents.get'; }
    public function description(): string { return 'Get a project document and extracted text summary.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['document_id' => ['type' => 'string']], 'required' => ['document_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var ProjectDocument $doc */
        $doc = $this->findInCompany(ProjectDocument::class, (string) ($arguments['document_id'] ?? ''), $context);
        $text = (string) ($doc->extracted_text ?? '');

        return [
            'id' => $doc->id,
            'name' => $doc->name,
            'status' => $doc->status,
            'mime_type' => $doc->mime_type,
            'project_id' => $doc->project_id,
            'extracted_text_preview' => mb_substr($text, 0, 2000),
            'extracted_text_length' => mb_strlen($text),
        ];
    }
}
