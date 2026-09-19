<?php

namespace App\AI\Nova\Document;

class DocumentChunker
{
    /**
     * @return list<array{index: int, text: string}>
     */
    public function chunk(string $text, int $size = 1800, int $overlap = 200): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $chunks = [];
        $len = mb_strlen($text);
        $i = 0;
        $index = 0;
        while ($i < $len) {
            $slice = mb_substr($text, $i, $size);
            $chunks[] = ['index' => $index, 'text' => $slice];
            $index++;
            $i += max(1, $size - $overlap);
        }

        return $chunks;
    }
}
