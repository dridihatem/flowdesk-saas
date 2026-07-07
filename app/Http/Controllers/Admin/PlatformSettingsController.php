<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformSettingsController extends Controller
{
    public function edit(): View
    {
        $row = PlatformSetting::query()->first() ?? new PlatformSetting;

        $rates = CurrencyRate::query()
            ->orderBy('base_currency')
            ->orderBy('quote_currency')
            ->get();

        return view('admin.platform-settings', [
            'settings' => $row,
            'rates' => $rates,
            'rateRows' => $this->buildRateRows($rates),
        ]);
    }

    /**
     * @return list<array{base_currency: string, quote_currency: string, rate: string, highlight: bool}>
     */
    private function buildRateRows($rates): array
    {
        $quotes = config('flowdesk.platform_exchange_currencies', ['EUR', 'GBP', 'TND', 'QAR']);
        $quotes = is_array($quotes) ? $quotes : ['EUR', 'GBP', 'TND', 'QAR'];
        $indexed = $rates->keyBy('quote_currency');
        $rows = [];

        foreach ($quotes as $quote) {
            $quote = strtoupper(trim((string) $quote));
            if ($quote === '' || $quote === 'USD') {
                continue;
            }
            $row = $indexed->get($quote);
            $rows[] = [
                'base_currency' => 'USD',
                'quote_currency' => $quote,
                'rate' => $row ? rtrim(rtrim(number_format((float) $row->rate, 8, '.', ''), '0'), '.') : '',
                'highlight' => $quote === 'QAR',
            ];
        }

        return $rows;
    }

    private function normalizeRateInput(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $rate = trim(str_replace(',', '.', (string) $value));
        $rate = preg_replace('/\s+/u', '', $rate) ?? $rate;

        return $rate;
    }

    public function update(Request $request): RedirectResponse
    {
        $normalizedRates = collect($request->input('rates', []))
            ->map(function (mixed $row): array {
                $row = is_array($row) ? $row : [];

                return [
                    'base_currency' => strtoupper(trim((string) ($row['base_currency'] ?? 'USD'))),
                    'quote_currency' => strtoupper(trim((string) ($row['quote_currency'] ?? ''))),
                    'rate' => $this->normalizeRateInput($row['rate'] ?? null),
                ];
            })
            ->values()
            ->all();

        $request->merge(['rates' => $normalizedRates]);

        $invalidRateMessage = __('admin_exchange_rate_invalid');

        $data = $request->validate([
            'ai_provider' => ['nullable', 'string', 'in:auto,anthropic,openai,google'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'openai_model' => ['nullable', 'string', 'max:64'],
            'anthropic_api_key' => ['nullable', 'string', 'max:500'],
            'claude_model' => ['nullable', 'string', 'max:64'],
            'google_api_key' => ['nullable', 'string', 'max:500'],
            'gemini_model' => ['nullable', 'string', 'max:64'],
            'tts_provider' => ['nullable', 'string', 'in:auto,edge,google,openai,browser'],
            'edge_tts_voice' => ['nullable', 'string', 'max:64'],
            'gemini_tts_model' => ['nullable', 'string', 'max:64'],
            'gemini_tts_voice' => ['nullable', 'string', 'max:32'],
            'openai_tts_voice' => ['nullable', 'string', 'max:32', 'in:alloy,ash,ballad,coral,echo,fable,nova,onyx,sage,shimmer,verse'],
            'openai_tts_model' => ['nullable', 'string', 'max:32'],

            'rates' => ['required', 'array', 'min:1'],
            'rates.*.base_currency' => ['required', 'string', 'size:3'],
            'rates.*.quote_currency' => ['required', 'string', 'size:3'],
            'rates.*.rate' => ['required', 'numeric', 'min:0.00000001', 'max:999999999'],
        ], [
            'rates.*.rate.required' => $invalidRateMessage,
            'rates.*.rate.numeric' => $invalidRateMessage,
            'rates.*.rate.min' => $invalidRateMessage,
            'rates.*.rate.max' => $invalidRateMessage,
        ]);

        $row = PlatformSetting::query()->first() ?? new PlatformSetting;

        $row->ai_provider = in_array($data['ai_provider'] ?? 'auto', ['auto', 'anthropic', 'openai', 'google'], true)
            ? ($data['ai_provider'] ?? 'auto')
            : 'auto';

        // Password fields submit empty when unchanged; Request::has() is true for "", which wrongly cleared keys.
        if ($request->boolean('clear_openai_api_key')) {
            $row->openai_api_key_encrypted = null;
        } elseif ($request->filled('openai_api_key')) {
            $row->openai_api_key_encrypted = $data['openai_api_key'];
        }
        $row->openai_model = ($data['openai_model'] ?? '') === '' ? null : $data['openai_model'];
        $row->openai_tts_voice = ($data['openai_tts_voice'] ?? '') === '' ? null : $data['openai_tts_voice'];
        $row->openai_tts_model = ($data['openai_tts_model'] ?? '') === '' ? null : $data['openai_tts_model'];
        if ($request->boolean('clear_anthropic_api_key')) {
            $row->anthropic_api_key_encrypted = null;
        } elseif ($request->filled('anthropic_api_key')) {
            $row->anthropic_api_key_encrypted = $data['anthropic_api_key'];
        }
        $row->claude_model = ($data['claude_model'] ?? '') === '' ? null : $data['claude_model'];
        if ($request->boolean('clear_google_api_key')) {
            $row->google_api_key_encrypted = null;
        } elseif ($request->filled('google_api_key')) {
            $row->google_api_key_encrypted = $data['google_api_key'];
        }
        $row->gemini_model = ($data['gemini_model'] ?? '') === '' ? null : $data['gemini_model'];
        $row->tts_provider = in_array($data['tts_provider'] ?? 'auto', ['auto', 'edge', 'google', 'openai', 'browser'], true)
            ? ($data['tts_provider'] ?? 'auto')
            : 'auto';
        $row->edge_tts_voice = ($data['edge_tts_voice'] ?? '') === '' ? null : $data['edge_tts_voice'];
        $row->gemini_tts_model = ($data['gemini_tts_model'] ?? '') === '' ? null : $data['gemini_tts_model'];
        $row->gemini_tts_voice = ($data['gemini_tts_voice'] ?? '') === '' ? null : $data['gemini_tts_voice'];
        $row->save();

        $rates = collect($data['rates'] ?? [])
            ->map(function (array $r): array {
                return [
                    'base_currency' => strtoupper(trim($r['base_currency'] ?? 'USD')),
                    'quote_currency' => strtoupper(trim($r['quote_currency'] ?? 'USD')),
                    'rate' => (string) ($r['rate'] ?? '1'),
                ];
            })
            ->filter(fn (array $r) => $r['base_currency'] !== '' && $r['quote_currency'] !== '')
            ->values();

        foreach ($rates as $r) {
            CurrencyRate::query()->updateOrCreate(
                ['base_currency' => $r['base_currency'], 'quote_currency' => $r['quote_currency']],
                ['rate' => $r['rate'], 'as_of' => now()],
            );
        }

        return redirect()->route('admin.platform-settings.edit')->with('status', __('Platform settings saved.'));
    }

    public function exportSql(Request $request): StreamedResponse
    {
        $tables = [
            'platform_settings',
            'currency_rates',
            'plans',
            'plan_limits',
            'companies',
            'company_settings',
            'users',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'subscriptions',
            'clients',
            'providers',
            'projects',
            'invoices',
            'invoice_items',
            'payments',
            'transactions',
        ];

        $pdo = DB::getPdo();
        $now = now()->format('Y-m-d_His');
        $filename = "flowdesk_export_{$now}.sql";

        return response()->streamDownload(function () use ($tables, $pdo): void {
            echo "-- Flowqil data export (data-only)\n";
            echo '-- Generated at: '.now()->toDateTimeString()."\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                if (empty($columns)) {
                    continue;
                }

                $rows = DB::table($table)->get($columns);
                if ($rows->isEmpty()) {
                    continue;
                }

                echo "-- Table: `{$table}`\n";

                $colSql = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

                foreach ($rows->chunk(250) as $chunk) {
                    $valuesSql = [];

                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ($columns as $c) {
                            $v = $row->{$c};
                            if ($v === null) {
                                $vals[] = 'NULL';

                                continue;
                            }

                            if (is_bool($v)) {
                                $vals[] = $v ? '1' : '0';

                                continue;
                            }

                            if (is_int($v) || is_float($v) || (is_string($v) && preg_match('/^-?\d+(\.\d+)?$/', $v))) {
                                $vals[] = (string) $v;

                                continue;
                            }

                            $vals[] = $pdo->quote((string) $v);
                        }

                        $valuesSql[] = '('.implode(', ', $vals).')';
                    }

                    echo "INSERT INTO `{$table}` ({$colSql}) VALUES\n";
                    echo implode(",\n", $valuesSql).";\n\n";
                }
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
        ]);
    }
}
