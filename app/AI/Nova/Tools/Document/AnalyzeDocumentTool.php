<?php

namespace App\AI\Nova\Tools\Document;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Document\DocumentAnalyzer;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectDocument;

class AnalyzeDocumentTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private DocumentAnalyzer $analyzer) {}

    public function name(): string { return 'documents.analyze'; }
    public function description(): string { return 'Analyze an uploaded project document or raw text/query for requirements.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'document_id' => ['type' => 'string'],
            'query' => ['type' => 'string'],
            'text' => ['type' => 'string'],
        ]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $text = (string) ($arguments['text'] ?? '');
        if (! empty($arguments['document_id'])) {
            /** @var ProjectDocument $doc */
            $doc = $this->findInCompany(ProjectDocument::class, (string) $arguments['document_id'], $context);
            $text = (string) ($doc->extracted_text ?: $text);
            if ($text === '') {
                return ['status' => 'pending_extraction', 'document_id' => $doc->id, 'message' => 'Document text is not extracted yet.'];
            }

            return $this->analyzer->analyzeText($text, $context, (string) ($arguments['query'] ?? ''));
        }

        if ($text === '' && ! empty($arguments['query'])) {
            $text = (string) $arguments['query'];
        }

        if (trim($text) === '') {
            return ['status' => 'needs_document', 'message' => 'Upload or select a document to analyze.'];
        }

        return $this->analyzer->analyzeText($text, $context, (string) ($arguments['query'] ?? ''));
    }
}
