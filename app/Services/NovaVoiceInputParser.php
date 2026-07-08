<?php

namespace App\Services;

class NovaVoiceInputParser
{
    /**
     * @param  list<string>  $locales
     */
    public static function parseLocale(string $text, array $locales): ?string
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return null;
        }

        $aliases = [
            'en' => ['english', 'anglais', 'inglés', 'ingles', 'inggris', 'अंग्रेज़ी', 'الإنجليزية'],
            'fr' => ['french', 'français', 'francais', 'francés', 'prancis', 'फ्रेंच', 'الفرنسية'],
            'es' => ['spanish', 'español', 'espanol', 'spanyol', 'स्पेनिश', 'الإسبانية'],
            'ar' => ['arabic', 'arabe', 'árabe', 'arab', 'arabisk', 'अरबी', 'العربية'],
            'id' => ['indonesian', 'indonésien', 'indonesio', 'bahasa indonesia', 'इंडोनेशियाई', 'الإندونيسية'],
            'hi' => ['hindi', 'hindou', 'hindú', 'हिंदी', 'الهندية'],
        ];

        foreach ($locales as $code) {
            if (preg_match('/\b'.preg_quote($code, '/').'\b/u', $normalized)) {
                return $code;
            }
        }

        foreach ($aliases as $code => $names) {
            if (! in_array($code, $locales, true)) {
                continue;
            }

            foreach ($names as $name) {
                if (str_contains($normalized, self::normalize($name))) {
                    return $code;
                }
            }
        }

        return null;
    }

    public static function extractEmail(string $text): ?string
    {
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/u', $text, $matches)) {
            return strtolower($matches[0]);
        }

        $spoken = self::normalizeSpokenEmailText($text);

        if (preg_match('/\b([a-z0-9._%+-]+)\s*(?:@|at|arobase|chez)\s*([a-z0-9-]+)\s*(?:\.|dot|point)\s*([a-z]{2,})\b/u', $spoken, $matches)) {
            return strtolower($matches[1].'@'.$matches[2].'.'.$matches[3]);
        }

        if (preg_match('/\b([a-z0-9._%+-]+)\s+at\s+([a-z0-9.-]+)\s+dot\s+([a-z]{2,})\b/u', $spoken, $matches)) {
            return strtolower($matches[1].'@'.$matches[2].'.'.$matches[3]);
        }

        $providers = 'gmail|yahoo|hotmail|outlook|icloud|live|protonmail|orange|free';
        if (preg_match('/\b([a-z0-9._%+-]+)\s+('.$providers.')\s*(?:\.|dot|point)?\s*(com|fr|net|org|co|uk|io)\b/u', $spoken, $matches)) {
            return strtolower($matches[1].'@'.$matches[2].'.'.$matches[3]);
        }

        if (preg_match('/\b([a-z0-9._%+-]+)\s+([a-z0-9-]+\.(?:com|fr|net|org|co\.uk|io))\b/u', $spoken, $matches)) {
            return strtolower($matches[1].'@'.$matches[2]);
        }

        return null;
    }

    public static function extractPhone(string $text): ?string
    {
        if (preg_match('/\+?\d[\d\s().-]{6,}\d/u', $text, $matches)) {
            $digits = preg_replace('/\D+/', '', $matches[0]) ?? '';

            return strlen($digits) >= 8 ? trim($matches[0]) : null;
        }

        return null;
    }

    public static function extractPercent(string $text): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:%|percent|pourcent|por ciento|taux|tva|vat)?/iu', $text, $matches)) {
            $value = (float) str_replace(',', '.', $matches[1]);

            return $value >= 0 && $value <= 100 ? $value : null;
        }

        return null;
    }

    public static function parseYesNo(string $text): ?bool
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return null;
        }

        $yes = [
            'yes', 'yeah', 'yep', 'sure', 'oui', 'ouais', 'si', 'sí', 'ja', 'ya',
            'create account', 'credential account', 'portal account', 'with account',
            'compte client', 'compte portail', 'creer compte', 'créer compte',
        ];
        $no = [
            'no', 'nope', 'non', 'nein', 'tidak', 'tidak perlu', 'sans compte',
            'no account', 'without account', 'pas de compte', 'sans compte',
        ];

        foreach ($yes as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        foreach ($no as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return false;
            }
        }

        return null;
    }

    /**
     * Parse a client/person name from free-form or step-specific voice input.
     */
    public static function extractName(string $text, ?string $email = null, ?string $phone = null): ?string
    {
        $email ??= self::extractEmail($text);
        $phone ??= self::extractPhone($text);

        return self::nameFromFragment($text, $email, $phone);
    }

    /**
     * Parse only a name field (collect_name step) — strips labels like "nom", "name".
     */
    public static function parseNameField(string $text): ?string
    {
        $fragment = self::extractLabeledFragment($text, [
            'nom complet',
            'nom',
            'full name',
            'name is',
            'my name is',
            'le nom est',
            'the name is',
            'name',
            'nama',
            'nombre',
        ]);

        return self::nameFromFragment($fragment ?? $text);
    }

    /**
     * @param  list<string>  $labels  Longest labels first.
     */
    private static function extractLabeledFragment(string $text, array $labels): ?string
    {
        $normalized = self::normalize($text);
        usort($labels, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($labels as $label) {
            $pattern = '/\b'.preg_quote(self::normalize($label), '/').'\s+(?:est\s+|is\s+)?(.+)$/u';
            if (preg_match($pattern, $normalized, $matches)) {
                $value = trim($matches[1]);

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private static function nameFromFragment(string $text, ?string $email = null, ?string $phone = null): ?string
    {
        $working = trim($text);
        if ($email) {
            $working = str_ireplace($email, ' ', $working);
        }
        if ($phone) {
            $working = str_ireplace($phone, ' ', $working);
        }

        $working = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/ui', ' ', $working) ?? $working;
        $working = preg_replace('/\b[a-z0-9._%+-]+\s+gmail(?:\s*(?:\.|dot|point)\s*|\s+)com\b/ui', ' ', $working) ?? $working;
        $working = preg_replace('/\+?\d[\d\s().-]{6,}\d/u', ' ', $working) ?? $working;

        $tokens = self::nameTokens(self::normalize($working));
        if ($tokens === []) {
            return null;
        }

        $tokens = array_values(array_unique($tokens));
        $tokens = array_slice($tokens, 0, 4);

        if ($tokens === []) {
            return null;
        }

        return mb_convert_case(implode(' ', $tokens), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @return list<string>
     */
    private static function nameTokens(string $normalized): array
    {
        if ($normalized === '') {
            return [];
        }

        $stopWords = [
            'yes', 'no', 'oui', 'non', 'ouais', 'si', 'sí', 'yeah', 'yep', 'nope',
            'email', 'e-mail', 'mail', 'courriel', 'adresse', 'address',
            'phone', 'telephone', 'téléphone', 'tel', 'mobile', 'numero', 'numéro', 'number',
            'name', 'nom', 'complet', 'complete', 'full', 'client', 'customer',
            'my', 'the', 'is', 'are', 'est', 'c', 'ce', "c'est", 'cest', 'et', 'and', 'de', 'du', 'des', 'le', 'la', 'les', 'un', 'une',
            'with', 'without', 'avec', 'sans', 'pour', 'mon', 'ma', 'mes', 'your',
            'create', 'account', 'portal', 'credential', 'compte', 'portail',
            'gmail', 'yahoo', 'hotmail', 'outlook', 'icloud', 'com', 'fr', 'net', 'org',
            'point', 'dot', 'arobase', 'at', 'chez', 'skip', 'none', 'aucun', 'pas',
            'say', 'dire', 'its', "it's", 'voici', 'here',
        ];

        $rawTokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $nameTokens = [];

        foreach ($rawTokens as $token) {
            $token = trim($token, ".,;:!?\"'");
            if ($token === '' || mb_strlen($token) < 2) {
                continue;
            }
            if (in_array($token, $stopWords, true)) {
                continue;
            }
            if (preg_match('/^\d+$/', $token)) {
                continue;
            }
            if (str_contains($token, '@') || str_contains($token, '.')) {
                continue;
            }

            $nameTokens[] = $token;
        }

        return $nameTokens;
    }

    public static function looksLikeMultiFieldInput(string $text): bool
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return false;
        }

        $fieldHints = [
            'email', 'e-mail', 'mail', 'adresse', 'courriel',
            'phone', 'telephone', 'téléphone', 'tel', 'mobile', 'numero', 'numéro',
            'portal', 'compte', 'account', 'gmail', 'yahoo', 'hotmail',
            'oui', 'non', 'yes', 'no', 'arobase', 'at', 'chez',
        ];

        $hits = 0;
        foreach ($fieldHints as $hint) {
            if (str_contains($normalized, $hint)) {
                $hits++;
            }
        }

        return $hits >= 2
            || self::extractEmail($text) !== null
            || self::extractPhone($text) !== null
            || self::parseYesNo($text) !== null;
    }

    private static function normalizeSpokenEmailText(string $text): string
    {
        $s = self::normalize($text);
        $s = preg_replace('/\b(?:email|e-mail|mail|courriel|adresse(?:\s+mail)?|c\'est|cest|c est|est|le|la|mon|ma|de|du)\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+(?:arobase|at|chez)\s+/u', '@', $s) ?? $s;
        $s = preg_replace('/\s+(?:point|dot)\s+/u', '.', $s) ?? $s;
        $s = preg_replace('/\s+@\s+/u', '@', $s) ?? $s;

        return preg_replace('/\s+/u', ' ', trim($s)) ?? '';
    }

    private static function normalize(string $text): string
    {
        $s = mb_strtolower(trim($text));
        $s = str_replace(['’', '`'], "'", $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return str_replace(['c est', 'c\'est'], "c'est", $s);
    }
}
