<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form as LeadForm;
use App\Models\FormSubmission;
use App\Models\WidgetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmbedFormController extends Controller
{
    public function optionsShow(LeadForm $form): JsonResponse
    {
        $this->assertFormTenant($form);

        return $this->withEmbedCors(response()->json(null, 204));
    }

    public function optionsStore(LeadForm $form): JsonResponse
    {
        $this->assertFormTenant($form);

        return $this->withEmbedCors(response()->json(null, 204));
    }

    public function show(Request $request, LeadForm $form): JsonResponse
    {
        $this->authorizeForm($form);

        $context = $this->embedPageContext($request);

        WidgetEvent::query()->withoutGlobalScopes()->create([
            'company_id' => $form->company_id,
            'form_id' => $form->id,
            'event' => 'view',
            'ip_address' => $request->ip(),
            'context' => $context,
        ]);

        $form->load(['fields' => fn ($q) => $q->orderBy('sort_order')]);

        $captchaEnabled = (bool) ($form->meta['captcha']['enabled'] ?? false);
        $captcha = $captchaEnabled ? $this->generateMathCaptcha($form) : null;

        return $this->withEmbedCors(response()->json([
            'widget_version' => $form->widget_version,
            'layout' => $form->layout,
            'meta' => $form->meta ?? [],
            'captcha' => $captcha,
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'status' => $form->status,
            ],
            'fields' => $form->fields->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'type' => $f->type,
                'required' => $f->required,
                'step' => $f->step ?? 0,
                'sort_order' => $f->sort_order,
                'placeholder' => $f->meta['placeholder'] ?? null,
                'options' => $f->meta['options'] ?? [],
            ]),
        ]));
    }

    public function store(Request $request, LeadForm $form): JsonResponse
    {
        $this->authorizeForm($form);

        if ($form->status !== 'published') {
            abort(403, __('This form is not published.'));
        }

        $captchaEnabled = (bool) ($form->meta['captcha']['enabled'] ?? false);
        if ($captchaEnabled) {
            $captchaError = $this->validateMathCaptcha($request, $form);
            if ($captchaError) {
                return $this->withEmbedCors(response()->json([
                    'message' => __('Validation failed.'),
                    'errors' => ['_captcha_answer' => [$captchaError]],
                ], 422));
            }
        }

        $form->load(['fields' => fn ($q) => $q->orderBy('sort_order')]);

        $decorativeTypes = ['heading', 'paragraph'];
        $rules = [];
        foreach ($form->fields as $field) {
            if (in_array($field->type, $decorativeTypes, true)) {
                continue;
            }
            $key = $field->name;
            $options = $field->meta['options'] ?? [];
            $base = $field->required ? 'required' : 'nullable';

            $rules[$key] = match ($field->type) {
                'number' => [$base, 'numeric'],
                'email' => [$base, 'email'],
                'url' => [$base, 'url', 'max:2048'],
                'date' => [$base, 'date'],
                'file' => [$base, 'file', 'max:10240'],
                'select', 'radio' => $options
                    ? [$base, 'string', 'in:'.implode(',', $options)]
                    : [$base, 'string'],
                'checkbox' => $options
                    ? [$base, 'array', ...[]]
                    : [$base],
                default => [$base, 'string'],
            };

            if ($field->type === 'checkbox' && $options) {
                $rules["{$key}.*"] = ['string', 'in:'.implode(',', $options)];
            }
        }

        $data = $request->all();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $this->withEmbedCors(response()->json(['message' => __('Validation failed.'), 'errors' => $validator->errors()], 422));
        }

        $payload = [];
        foreach ($form->fields as $field) {
            if (in_array($field->type, $decorativeTypes, true)) {
                continue;
            }
            if ($field->type === 'file') {
                $file = $request->file($field->name);
                $payload[$field->name] = $file ? $file->store("form-uploads/{$form->id}", 'public') : null;
            } else {
                $payload[$field->name] = $data[$field->name] ?? null;
            }
        }

        FormSubmission::query()->withoutGlobalScopes()->create([
            'company_id' => $form->company_id,
            'form_id' => $form->id,
            'data' => $payload,
            'ip_address' => $request->ip(),
        ]);

        $context = $this->embedPageContext($request);

        WidgetEvent::query()->withoutGlobalScopes()->create([
            'company_id' => $form->company_id,
            'form_id' => $form->id,
            'event' => 'submit',
            'ip_address' => $request->ip(),
            'context' => $context,
        ]);

        return $this->withEmbedCors(response()->json(['message' => __('Thank you. Your submission was received.')]));
    }

    private function assertFormTenant(LeadForm $form): void
    {
        $company = app()->bound('currentCompany') ? app('currentCompany') : null;
        abort_if(! $company || (string) $form->company_id !== (string) $company->id, 404);
    }

    private function authorizeForm(LeadForm $form): void
    {
        $this->assertFormTenant($form);
    }

    /**
     * Optional base64(JSON) header from the embed script: page_url, path, referrer, title.
     */
    private function embedPageContext(Request $request): ?array
    {
        $raw = $request->header('X-Flowdesk-Context');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = base64_decode($raw, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $data = json_decode($decoded, true);
        if (! is_array($data)) {
            return null;
        }

        $out = [
            'page_url' => isset($data['page_url']) ? Str::limit((string) $data['page_url'], 2048, '') : null,
            'path' => isset($data['path']) ? Str::limit((string) $data['path'], 1024, '') : null,
            'referrer' => isset($data['referrer']) ? Str::limit((string) $data['referrer'], 2048, '') : null,
            'title' => isset($data['title']) ? Str::limit((string) $data['title'], 500, '') : null,
        ];

        foreach ($out as $k => $v) {
            if ($v === '') {
                $out[$k] = null;
            }
        }

        if ($out['path'] === null && $out['page_url'] !== null) {
            $p = parse_url($out['page_url'], PHP_URL_PATH);
            $out['path'] = is_string($p) && $p !== '' ? $p : '/';
        }

        $filtered = array_filter($out, fn ($v) => $v !== null && $v !== '');

        return $filtered === [] ? null : $out;
    }

    /**
     * Generate a math CAPTCHA challenge (e.g. "7 + 3 = ?").
     * Returns the question text and an HMAC-signed token the client must send back.
     */
    private function generateMathCaptcha(LeadForm $form): array
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
        $payload = "{$form->id}|{$answer}|{$expiresAt}";
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return [
            'question' => "{$a} {$op} {$b} = ?",
            'token' => base64_encode("{$answer}|{$expiresAt}|{$signature}"),
        ];
    }

    /**
     * Validate the captcha answer + token submitted by the client.
     * Returns an error string on failure, null on success.
     */
    private function validateMathCaptcha(Request $request, LeadForm $form): ?string
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

        $payload = "{$form->id}|{$expectedAnswer}|{$expiresAt}";
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
