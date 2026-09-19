<?php

namespace App\AI\Nova\Document;

/**
 * OCR stub — architecture placeholder for image/PDF OCR.
 * Production OCR will use Gemini multimodal / dedicated OCR services.
 */
class OCRProcessor
{
    public function supports(string $mimeType): bool
    {
        $mime = strtolower($mimeType);

        return str_starts_with($mime, 'image/') || $mime === 'application/pdf';
    }

    /**
     * @return array{status: string, text: string, message: string}
     */
    public function process(string $absolutePath, string $mimeType): array
    {
        return [
            'status' => 'stubbed',
            'text' => '',
            'message' => 'OCR is scaffolded. Use Gemini document analysis for PDF/images when a platform key is configured.',
        ];
    }
}
