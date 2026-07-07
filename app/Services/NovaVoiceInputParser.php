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

        $spoken = self::normalize($text);
        if (preg_match('/\b([a-z0-9._%+-]+)\s+at\s+([a-z0-9.-]+)\s+dot\s+([a-z]{2,})\b/u', $spoken, $matches)) {
            return strtolower($matches[1].'@'.$matches[2].'.'.$matches[3]);
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

    public static function extractName(string $text, ?string $email = null, ?string $phone = null): ?string
    {
        $working = trim($text);
        if ($email) {
            $working = str_ireplace($email, ' ', $working);
        }
        if ($phone) {
            $working = str_ireplace($phone, ' ', $working);
        }

        $working = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/u', ' ', $working) ?? $working;
        $working = preg_replace('/\+?\d[\d\s().-]{6,}\d/u', ' ', $working) ?? $working;

        $normalized = self::normalize($working);
        $normalized = preg_replace('/\b(yes|no|oui|non|email|phone|telephone|téléphone|name|nom|full name|nom complet|my name is|le nom est|the name is|create account|portal account|credential account|compte|client)\b/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';

        if (mb_strlen($normalized) < 2) {
            return null;
        }

        return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }

    private static function normalize(string $text): string
    {
        $s = mb_strtolower(trim($text));
        $s = str_replace(['’', '`'], "'", $s);

        return preg_replace('/\s+/u', ' ', $s) ?? $s;
    }
}
