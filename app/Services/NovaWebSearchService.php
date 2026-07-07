<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NovaWebSearchService
{
    /**
     * Build a text block of web snippets for the LLM (DuckDuckGo instant answers).
     */
    public function searchSnippets(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return '';
        }

        try {
            $response = Http::timeout(12)
                ->connectTimeout(8)
                ->withHeaders(['User-Agent' => 'FlowDesk-Nova/1.0'])
                ->get('https://api.duckduckgo.com/', [
                    'q' => $query,
                    'format' => 'json',
                    'no_redirect' => 1,
                    'no_html' => 1,
                    'skip_disambig' => 1,
                ]);
        } catch (\Throwable $e) {
            Log::info('Nova web search failed', ['message' => $e->getMessage()]);

            return '';
        }

        if (! $response->successful()) {
            return '';
        }

        $data = $response->json();
        if (! is_array($data)) {
            return '';
        }

        $lines = [];

        $heading = trim((string) ($data['Heading'] ?? ''));
        $abstract = trim((string) ($data['AbstractText'] ?? ''));
        $abstractUrl = trim((string) ($data['AbstractURL'] ?? ''));
        if ($abstract !== '') {
            $lines[] = $heading !== '' ? "{$heading}: {$abstract}" : $abstract;
            if ($abstractUrl !== '') {
                $lines[] = 'Source: '.$abstractUrl;
            }
        }

        $related = $data['RelatedTopics'] ?? [];
        if (is_array($related)) {
            $count = 0;
            foreach ($related as $topic) {
                if ($count >= 5) {
                    break;
                }
                if (! is_array($topic)) {
                    continue;
                }
                if (isset($topic['Topics']) && is_array($topic['Topics'])) {
                    foreach ($topic['Topics'] as $sub) {
                        if ($count >= 5 || ! is_array($sub)) {
                            continue;
                        }
                        $snippet = $this->formatRelatedTopic($sub);
                        if ($snippet !== '') {
                            $lines[] = $snippet;
                            $count++;
                        }
                    }

                    continue;
                }
                $snippet = $this->formatRelatedTopic($topic);
                if ($snippet !== '') {
                    $lines[] = $snippet;
                    $count++;
                }
            }
        }

        $answer = trim((string) ($data['Answer'] ?? ''));
        if ($answer !== '') {
            $lines[] = 'Quick answer: '.$answer;
        }

        return implode("\n", array_unique(array_filter($lines)));
    }

    /**
     * @param  array<string, mixed>  $topic
     */
    private function formatRelatedTopic(array $topic): string
    {
        $text = trim((string) ($topic['Text'] ?? ''));
        $url = trim((string) ($topic['FirstURL'] ?? ''));
        if ($text === '') {
            return '';
        }

        return $url !== '' ? "- {$text} ({$url})" : "- {$text}";
    }

    public function hasUsefulSnippets(string $snippets): bool
    {
        return Str::length(trim($snippets)) >= 40;
    }
}
