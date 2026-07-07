<?php

namespace App\Services;

use App\Enums\ClientSource;
use App\Enums\ClientStatus;
use App\Enums\HrEmployeeStatus;
use App\Enums\HrEmploymentType;
use App\Enums\HrPayFrequency;
use App\Models\Client;
use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NovaVoiceWorkflowService
{
    private const CACHE_TTL_MINUTES = 20;

    public function __construct(
        private ClientCodeService $clientCodes,
        private HrSetupService $hrSetup,
        private CompanyThemeService $themes,
    ) {}

    /**
     * @return list<array{id: string, label: string, phrases: list<string>}>
     */
    public function workflowsFor(User $user, array $gates): array
    {
        $workflows = [];

        foreach ($this->definitions() as $id => $definition) {
            if (! ($definition['visible'])($user, $gates)) {
                continue;
            }

            $workflows[] = [
                'id' => $id,
                'label' => __((string) $definition['label_key']),
                'phrases' => $this->phrasesFor((string) $definition['phrase_key']),
            ];
        }

        return $workflows;
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    public function start(User $user, string $workflowId): array
    {
        $definition = $this->definitions()[$workflowId] ?? null;
        if ($definition === null) {
            throw new \InvalidArgumentException(__('nova_workflow_unknown'));
        }

        $company = $user->company;
        if (! $company) {
            throw new \InvalidArgumentException(__('nova_workflow_no_company'));
        }

        $this->putSession($user, [
            'workflow' => $workflowId,
            'step' => 'awaiting_details',
            'data' => [],
        ]);

        return [
            'active' => true,
            'workflow' => $workflowId,
            'reply' => __((string) $definition['prompt_key']),
            'done' => false,
            'redirect_url' => null,
        ];
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    public function advance(User $user, string $input): array
    {
        $session = $this->getSession($user);
        if ($session === null) {
            throw new \InvalidArgumentException(__('nova_workflow_none_active'));
        }

        $workflowId = (string) ($session['workflow'] ?? '');
        $definition = $this->definitions()[$workflowId] ?? null;
        if ($definition === null) {
            $this->clearSession($user);

            throw new \InvalidArgumentException(__('nova_workflow_unknown'));
        }

        $input = trim($input);
        if ($input === '') {
            return $this->activeReply($workflowId, __('nova_workflow_empty_input'));
        }

        if ($this->isCancelPhrase($input)) {
            $this->clearSession($user);

            return [
                'active' => false,
                'workflow' => null,
                'reply' => __('nova_workflow_cancelled'),
                'done' => true,
                'redirect_url' => null,
            ];
        }

        return match ($workflowId) {
            'create_client' => $this->advanceCreateClient($user, $session, $input),
            'create_hr_employee' => $this->advanceCreateHrEmployee($user, $session, $input),
            'create_provider' => $this->advanceCreateProvider($user, $session, $input),
            'update_vat' => $this->advanceUpdateVat($user, $session, $input),
            'update_locale' => $this->advanceUpdateLocale($user, $session, $input),
            default => throw new \InvalidArgumentException(__('nova_workflow_unknown')),
        };
    }

    public function hasActiveSession(User $user): bool
    {
        return $this->getSession($user) !== null;
    }

    public function clearSession(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function advanceCreateClient(User $user, array $session, string $input): array
    {
        $data = is_array($session['data'] ?? null) ? $session['data'] : [];
        $step = (string) ($session['step'] ?? 'awaiting_details');

        if ($step === 'awaiting_details') {
            $data = $this->mergeClientFields($data, $input);
            $missing = $this->missingClientFields($data);

            if ($missing === []) {
                return $this->executeCreateClient($user, $data);
            }

            $nextStep = $missing[0];
            $this->putSession($user, ['workflow' => 'create_client', 'step' => $nextStep, 'data' => $data]);

            return $this->activeReply('create_client', $this->clientFieldPrompt($nextStep));
        }

        $data = $this->applyClientStep($step, $data, $input);
        $missing = $this->missingClientFields($data);

        if ($missing !== []) {
            $nextStep = $missing[0];
            $this->putSession($user, ['workflow' => 'create_client', 'step' => $nextStep, 'data' => $data]);

            return $this->activeReply('create_client', $this->clientFieldPrompt($nextStep));
        }

        return $this->executeCreateClient($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeClientFields(array $data, string $input): array
    {
        $email = NovaVoiceInputParser::extractEmail($input);
        $phone = NovaVoiceInputParser::extractPhone($input);
        $portal = NovaVoiceInputParser::parseYesNo($input);
        $name = NovaVoiceInputParser::extractName($input, $email, $phone);

        if ($name && empty($data['name'])) {
            $data['name'] = $name;
        }
        if ($email && empty($data['email'])) {
            $data['email'] = $email;
        }
        if ($phone && empty($data['phone'])) {
            $data['phone'] = $phone;
        }
        if ($portal !== null && ! isset($data['create_portal'])) {
            $data['create_portal'] = $portal;
        }

        if (empty($data['name']) && ! NovaVoiceInputParser::extractEmail($input) && ! NovaVoiceInputParser::extractPhone($input)) {
            $plain = trim($input);
            if (mb_strlen($plain) >= 2 && NovaVoiceInputParser::parseYesNo($plain) === null) {
                $data['name'] = $plain;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyClientStep(string $step, array $data, string $input): array
    {
        return match ($step) {
            'collect_name' => array_merge($data, ['name' => trim($input)]),
            'collect_email' => array_merge($data, ['email' => $this->optionalContactValue($input, true)]),
            'collect_phone' => array_merge($data, ['phone' => $this->optionalContactValue($input, false)]),
            'collect_portal' => array_merge($data, ['create_portal' => NovaVoiceInputParser::parseYesNo($input) ?? false]),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function missingClientFields(array $data): array
    {
        $missing = [];
        if (empty($data['name'])) {
            $missing[] = 'collect_name';
        }
        if (! array_key_exists('email', $data)) {
            $missing[] = 'collect_email';
        }
        if (! array_key_exists('phone', $data)) {
            $missing[] = 'collect_phone';
        }
        if (! array_key_exists('create_portal', $data)) {
            $missing[] = 'collect_portal';
        }

        return $missing;
    }

    private function clientFieldPrompt(string $step): string
    {
        return match ($step) {
            'collect_name' => __('nova_workflow_ask_name'),
            'collect_email' => __('nova_workflow_ask_email'),
            'collect_phone' => __('nova_workflow_ask_phone'),
            'collect_portal' => __('nova_workflow_ask_portal'),
            default => __('nova_workflow_create_client_prompt'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function executeCreateClient(User $user, array $data): array
    {
        $company = $user->company;
        abort_if(! $company, 403);

        $createPortal = (bool) ($data['create_portal'] ?? false);
        $email = isset($data['email']) && $data['email'] !== '' ? (string) $data['email'] : null;

        if ($createPortal && $email === null) {
            $this->putSession($user, ['workflow' => 'create_client', 'step' => 'collect_email', 'data' => $data]);

            return $this->activeReply('create_client', __('nova_workflow_need_email_for_portal'));
        }

        $client = Client::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => (string) $data['name'],
            'email' => $email,
            'phone' => isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null,
            'source' => ClientSource::Other->value,
            'status' => ClientStatus::Active->value,
        ]);

        $this->clientCodes->assignIfMissing($client);

        if ($createPortal && $email) {
            $this->createPortalUser($user, $client, $email, sendCredentials: true);
        }

        $this->clearSession($user);

        return [
            'active' => false,
            'workflow' => null,
            'reply' => __('nova_workflow_create_client_success', ['name' => $client->name]),
            'done' => true,
            'redirect_url' => Route::has('clients.index') ? route('clients.index') : null,
        ];
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function advanceCreateHrEmployee(User $user, array $session, string $input): array
    {
        $data = is_array($session['data'] ?? null) ? $session['data'] : [];
        $step = (string) ($session['step'] ?? 'awaiting_details');

        if ($step === 'awaiting_details') {
            $data = $this->mergeHrFields($data, $input);
        } else {
            $data = $this->applyHrStep($step, $data, $input);
        }

        $missing = $this->missingHrFields($data);
        if ($missing !== []) {
            $nextStep = $missing[0];
            $this->putSession($user, ['workflow' => 'create_hr_employee', 'step' => $nextStep, 'data' => $data]);

            return $this->activeReply('create_hr_employee', $this->hrFieldPrompt($nextStep));
        }

        return $this->executeCreateHrEmployee($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeHrFields(array $data, string $input): array
    {
        $email = NovaVoiceInputParser::extractEmail($input);
        $phone = NovaVoiceInputParser::extractPhone($input);
        $name = NovaVoiceInputParser::extractName($input, $email, $phone);

        if ($name && empty($data['full_name'])) {
            $data['full_name'] = $name;
        }
        if ($email && empty($data['email'])) {
            $data['email'] = $email;
        }
        if ($phone && empty($data['phone'])) {
            $data['phone'] = $phone;
        }

        $job = $this->extractJobTitle($input);
        if ($job && empty($data['job_title'])) {
            $data['job_title'] = $job;
        }

        if (empty($data['full_name']) && trim($input) !== '') {
            $data['full_name'] = trim($input);
        }

        return $data;
    }

    private function extractJobTitle(string $input): ?string
    {
        if (preg_match('/\b(?:job title|poste|titre|position|role)\s+(?:is\s+)?(.+)$/iu', $input, $matches)) {
            $title = trim($matches[1]);
            $title = preg_replace('/\b(email|phone|telephone).*/iu', '', $title) ?? $title;

            return trim($title) !== '' ? trim($title) : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyHrStep(string $step, array $data, string $input): array
    {
        return match ($step) {
            'collect_full_name' => array_merge($data, ['full_name' => trim($input)]),
            'collect_email' => array_merge($data, ['email' => $this->optionalContactValue($input, true)]),
            'collect_phone' => array_merge($data, ['phone' => $this->optionalContactValue($input, false)]),
            'collect_job_title' => array_merge($data, ['job_title' => $this->nullableTextField($input)]),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function missingHrFields(array $data): array
    {
        $missing = [];
        if (empty($data['full_name'])) {
            $missing[] = 'collect_full_name';
        }
        if (! array_key_exists('email', $data)) {
            $missing[] = 'collect_email';
        }
        if (! array_key_exists('phone', $data)) {
            $missing[] = 'collect_phone';
        }
        if (! array_key_exists('job_title', $data)) {
            $missing[] = 'collect_job_title';
        }

        return $missing;
    }

    private function hrFieldPrompt(string $step): string
    {
        return match ($step) {
            'collect_full_name' => __('nova_workflow_ask_full_name'),
            'collect_email' => __('nova_workflow_ask_email'),
            'collect_phone' => __('nova_workflow_ask_phone'),
            'collect_job_title' => __('nova_workflow_ask_job_title'),
            default => __('nova_workflow_create_hr_employee_prompt'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function executeCreateHrEmployee(User $user, array $data): array
    {
        $company = $user->company;
        abort_if(! $company, 403);

        HrEmployee::query()->create([
            'company_id' => $company->id,
            'full_name' => (string) $data['full_name'],
            'email' => isset($data['email']) && $data['email'] !== '' ? (string) $data['email'] : null,
            'phone' => isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null,
            'job_title' => isset($data['job_title']) && $data['job_title'] !== '' ? (string) $data['job_title'] : null,
            'employee_number' => $this->hrSetup->nextEmployeeNumber($company),
            'employment_type' => HrEmploymentType::FullTime->value,
            'status' => HrEmployeeStatus::Active->value,
            'pay_frequency' => HrPayFrequency::Monthly->value,
        ]);

        $this->clearSession($user);

        return [
            'active' => false,
            'workflow' => null,
            'reply' => __('nova_workflow_create_hr_success', ['name' => (string) $data['full_name']]),
            'done' => true,
            'redirect_url' => Route::has('hr.employees.index') ? route('hr.employees.index') : null,
        ];
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function advanceCreateProvider(User $user, array $session, string $input): array
    {
        $data = is_array($session['data'] ?? null) ? $session['data'] : [];
        $step = (string) ($session['step'] ?? 'awaiting_details');

        if ($step === 'awaiting_details') {
            $data = $this->mergeProviderFields($data, $input);
        } else {
            $data = $this->applyProviderStep($step, $data, $input);
        }

        $missing = $this->missingProviderFields($data);
        if ($missing !== []) {
            $nextStep = $missing[0];
            $this->putSession($user, ['workflow' => 'create_provider', 'step' => $nextStep, 'data' => $data]);

            return $this->activeReply('create_provider', $this->providerFieldPrompt($nextStep));
        }

        return $this->executeCreateProvider($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeProviderFields(array $data, string $input): array
    {
        $email = NovaVoiceInputParser::extractEmail($input);
        $phone = NovaVoiceInputParser::extractPhone($input);
        $name = NovaVoiceInputParser::extractName($input, $email, $phone);

        if ($name && empty($data['name'])) {
            $data['name'] = $name;
        }
        if ($email && empty($data['email'])) {
            $data['email'] = $email;
        }
        if ($phone && empty($data['phone'])) {
            $data['phone'] = $phone;
        }
        if (empty($data['name']) && trim($input) !== '') {
            $data['name'] = trim($input);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyProviderStep(string $step, array $data, string $input): array
    {
        return match ($step) {
            'collect_name' => array_merge($data, ['name' => trim($input)]),
            'collect_email' => array_merge($data, ['email' => $this->optionalContactValue($input, true)]),
            'collect_phone' => array_merge($data, ['phone' => $this->optionalContactValue($input, false)]),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function missingProviderFields(array $data): array
    {
        $missing = [];
        if (empty($data['name'])) {
            $missing[] = 'collect_name';
        }
        if (! array_key_exists('email', $data)) {
            $missing[] = 'collect_email';
        }
        if (! array_key_exists('phone', $data)) {
            $missing[] = 'collect_phone';
        }

        return $missing;
    }

    private function providerFieldPrompt(string $step): string
    {
        return match ($step) {
            'collect_name' => __('nova_workflow_ask_name'),
            'collect_email' => __('nova_workflow_ask_email'),
            'collect_phone' => __('nova_workflow_ask_phone'),
            default => __('nova_workflow_create_provider_prompt'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function executeCreateProvider(User $user, array $data): array
    {
        $company = $user->company;
        abort_if(! $company, 403);

        Provider::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => (string) $data['name'],
            'email' => isset($data['email']) && $data['email'] !== '' ? (string) $data['email'] : null,
            'phone' => isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null,
        ]);

        $this->clearSession($user);

        return [
            'active' => false,
            'workflow' => null,
            'reply' => __('nova_workflow_create_provider_success', ['name' => (string) $data['name']]),
            'done' => true,
            'redirect_url' => Route::has('providers.index') ? route('providers.index') : null,
        ];
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function advanceUpdateVat(User $user, array $session, string $input): array
    {
        $rate = NovaVoiceInputParser::extractPercent($input);
        if ($rate === null) {
            return $this->activeReply('update_vat', __('nova_workflow_invalid_vat'));
        }

        $company = $user->company;
        abort_if(! $company, 403);

        $settings = $this->themes->ensureSettings($company);
        $prev = is_array($settings->billing) ? $settings->billing : [];
        $settings->billing = array_merge($prev, ['vat_percent' => $rate]);
        $settings->save();

        $this->clearSession($user);

        return [
            'active' => false,
            'workflow' => null,
            'reply' => __('nova_workflow_update_vat_success', ['rate' => $rate]),
            'done' => true,
            'redirect_url' => Route::has('settings.billing-tax') ? route('settings.billing-tax') : null,
        ];
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function advanceUpdateLocale(User $user, array $session, string $input): array
    {
        $locales = config('flowdesk.locales', ['en']);
        $locale = NovaVoiceInputParser::parseLocale($input, $locales);
        if ($locale === null) {
            return $this->activeReply('update_locale', __('nova_workflow_invalid_locale'));
        }

        $company = $user->company;
        abort_if(! $company, 403);

        $company->update(['default_locale' => $locale]);
        $this->clearSession($user);

        $label = match ($locale) {
            'fr' => __('French'),
            'es' => __('Spanish'),
            'ar' => __('Arabic'),
            'id' => __('Indonesian'),
            'hi' => __('Hindi'),
            default => __('English'),
        };

        return [
            'active' => false,
            'workflow' => null,
            'reply' => __('nova_workflow_update_locale_success', ['locale' => $label]),
            'done' => true,
            'redirect_url' => Route::has('settings.workspace-locale') ? route('settings.workspace-locale') : null,
        ];
    }

    private function createPortalUser(User $user, Client $client, string $email, bool $sendCredentials): void
    {
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('client_portal_email_taken'),
            ]);
        }

        $password = Str::password(12);

        DB::transaction(function () use ($user, $client, $email, $password): void {
            $portalUser = User::query()->create([
                'name' => $client->name,
                'email' => $email,
                'password' => Hash::make($password),
                'company_id' => $client->company_id,
                'locale' => $user->locale,
                'email_verified_at' => now(),
            ]);
            $portalUser->assignRole('client');
            $client->update(['user_id' => $portalUser->id]);
        });

        if ($sendCredentials) {
            try {
                app(ClientCredentialsMailService::class)->send($client, $user->company, $password);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @return array{active: bool, workflow: ?string, reply: string, done: bool, redirect_url: ?string}
     */
    private function activeReply(string $workflowId, string $reply): array
    {
        return [
            'active' => true,
            'workflow' => $workflowId,
            'reply' => $reply,
            'done' => false,
            'redirect_url' => null,
        ];
    }

    private function optionalContactValue(string $input, bool $email): ?string
    {
        $normalized = mb_strtolower(trim($input));
        $skip = ['skip', 'none', 'no', 'non', 'pas de', 'sans', 'tidak', 'aucun', 'n/a', 'na'];
        foreach ($skip as $phrase) {
            if ($normalized === $phrase || str_starts_with($normalized, $phrase.' ')) {
                return $email ? null : '';
            }
        }

        if ($email) {
            return NovaVoiceInputParser::extractEmail($input) ?? trim($input);
        }

        return NovaVoiceInputParser::extractPhone($input) ?? trim($input);
    }

    private function nullableTextField(string $input): ?string
    {
        $normalized = mb_strtolower(trim($input));
        $skip = ['skip', 'none', 'no', 'non', 'pas de', 'sans', 'tidak', 'aucun', 'n/a', 'na'];
        foreach ($skip as $phrase) {
            if ($normalized === $phrase || str_starts_with($normalized, $phrase.' ')) {
                return null;
            }
        }

        $value = trim($input);

        return $value === '' ? null : $value;
    }

    private function isCancelPhrase(string $input): bool
    {
        $normalized = mb_strtolower(trim($input));

        return in_array($normalized, ['cancel', 'annuler', 'stop', 'abort', 'quit', 'quitter'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSession(User $user): ?array
    {
        $session = Cache::get($this->cacheKey($user));

        return is_array($session) ? $session : null;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function putSession(User $user, array $session): void
    {
        Cache::put($this->cacheKey($user), $session, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    private function cacheKey(User $user): string
    {
        return 'nova_voice_workflow:'.$user->id;
    }

    /**
     * @return list<string>
     */
    private function phrasesFor(string $key): array
    {
        $groups = Lang::get('nova_voice.workflow_phrases');
        if (! is_array($groups)) {
            return [];
        }

        $phrases = $groups[$key] ?? null;
        if (! is_array($phrases)) {
            return [];
        }

        $normalized = [];
        foreach ($phrases as $phrase) {
            if (! is_scalar($phrase)) {
                continue;
            }

            $value = mb_strtolower(trim((string) $phrase));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return array<string, array{label_key: string, phrase_key: string, prompt_key: string, visible: callable}>
     */
    private function definitions(): array
    {
        $canClients = fn (User $u) => $u->can('workspace.manage_clients');
        $canProviders = fn (User $u, array $g) => ($g['providers'] ?? true) && $u->can('workspace.manage_providers');
        $canHr = fn (User $u, array $g) => ($g['hr'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']);
        $canSettings = fn (User $u) => $u->hasAnyRole(['company_admin', 'team_member']);

        return [
            'create_client' => [
                'label_key' => 'nova_workflow_label_create_client',
                'phrase_key' => 'create_client',
                'prompt_key' => 'nova_workflow_create_client_prompt',
                'visible' => fn (User $u, array $g) => $canClients($u),
            ],
            'create_hr_employee' => [
                'label_key' => 'nova_workflow_label_create_hr_employee',
                'phrase_key' => 'create_hr_employee',
                'prompt_key' => 'nova_workflow_create_hr_employee_prompt',
                'visible' => $canHr,
            ],
            'create_provider' => [
                'label_key' => 'nova_workflow_label_create_provider',
                'phrase_key' => 'create_provider',
                'prompt_key' => 'nova_workflow_create_provider_prompt',
                'visible' => $canProviders,
            ],
            'update_vat' => [
                'label_key' => 'nova_workflow_label_update_vat',
                'phrase_key' => 'update_vat',
                'prompt_key' => 'nova_workflow_update_vat_prompt',
                'visible' => $canSettings,
            ],
            'update_locale' => [
                'label_key' => 'nova_workflow_label_update_locale',
                'phrase_key' => 'update_locale',
                'prompt_key' => 'nova_workflow_update_locale_prompt',
                'visible' => $canSettings,
            ],
        ];
    }
}
