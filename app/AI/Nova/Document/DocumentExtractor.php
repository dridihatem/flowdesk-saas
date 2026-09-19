<?php

namespace App\AI\Nova\Document;

/**
 * Extracts plain text from uploaded files. OCR/PDF heavy lifting is delegated
 * later; CSV/text are handled locally.
 */
class DocumentExtractor
{
    public function extract(string $absolutePath, string $mimeType): string
    {
        $mime = strtolower($mimeType);
        if (str_starts_with($mime, 'text/') || in_array($mime, ['application/csv', 'text/csv'], true)) {
            return (string) file_get_contents($absolutePath);
        }

        // Binary formats: return empty and let DocumentAnalyzer / LLM path handle
        // via PlatformLlmRouter::completeWithDocument when base64 is provided.
        return '';
    }
}
