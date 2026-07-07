<?php

namespace App\Services;

use Illuminate\Http\Request;

class MathCaptchaService
{
    /**
     * @return array{question: string, token: string}
     */
    public function generate(string $context): array
    {
        $operators = ['+', '-', '×'];
        $op = $operators[array_rand($operators)];

        if ($op === '-') {
            $a = random_int(5, 20);
            $b = random_int(1, $a);
            $answer = $a - $b;
        } elseif ($op === '×') {
            $a = random_int(2, 9);
            $b = random_int(2, 9);
            $answer = $a * $b;
        } else {
            $a = random_int(1, 15);
            $b = random_int(1, 15);
            $answer = $a + $b;
        }

        $expiresAt = time() + 600;
        $payload = "{$context}|{$answer}|{$expiresAt}";
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return [
            'question' => "{$a} {$op} {$b} = ?",
            'token' => base64_encode("{$answer}|{$expiresAt}|{$signature}"),
        ];
    }

    public function validate(Request $request, string $context): ?string
    {
        $userAnswer = $request->input('_captcha_answer');
        $token = $request->input('_captcha_token');

        if (! is_string($token) || $token === '' || ! is_numeric($userAnswer)) {
            return __('Please solve the math question.');
        }

        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return __('Invalid captcha token.');
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return __('Invalid captcha token.');
        }

        [$expectedAnswer, $expiresAt, $signature] = $parts;

        if ((int) $expiresAt < time()) {
            return __('Captcha expired. Please reload the form.');
        }

        $payload = "{$context}|{$expectedAnswer}|{$expiresAt}";
        $expectedSig = hash_hmac('sha256', $payload, config('app.key'));

        if (! hash_equals($expectedSig, $signature)) {
            return __('Invalid captcha token.');
        }

        if ((int) $userAnswer !== (int) $expectedAnswer) {
            return __('Incorrect answer. Please try again.');
        }

        return null;
    }
}
