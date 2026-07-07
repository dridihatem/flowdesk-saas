<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ZoomMeetingService
{
    public function isConfigured(Company $company): bool
    {
        return $this->credentials($company) !== null;
    }

    /**
     * @return array{join_url: string, id: string}|null
     */
    public function createScheduledMeeting(Company $company, string $topic, string $startIso, int $durationMinutes = 60): ?array
    {
        $creds = $this->credentials($company);
        if ($creds === null) {
            return null;
        }

        $token = $this->accessToken($creds);
        if ($token === null) {
            return null;
        }

        $tz = (string) config('app.timezone', 'UTC');

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post('https://api.zoom.us/v2/users/me/meetings', [
                    'topic' => mb_substr($topic, 0, 200),
                    'type' => 2,
                    'start_time' => $startIso,
                    'duration' => max(15, $durationMinutes),
                    'timezone' => $tz,
                    'settings' => [
                        'join_before_host' => true,
                        'waiting_room' => false,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('zoom.meeting.create', [
                    'company_id' => $company->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return [
                'join_url' => (string) ($data['join_url'] ?? ''),
                'id' => (string) ($data['id'] ?? ''),
            ];
        } catch (Throwable $e) {
            Log::warning('zoom.meeting.create_exception', [
                'company_id' => $company->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{account_id: string, client_id: string, client_secret: string}|null
     */
    private function credentials(Company $company): ?array
    {
        $settings = $company->settings;
        if (! $settings || ! filled($settings->zoom_account_id)) {
            return null;
        }

        try {
            $clientId = (string) ($settings->zoom_client_id_encrypted ?? '');
            $clientSecret = (string) ($settings->zoom_client_secret_encrypted ?? '');
        } catch (Throwable) {
            return null;
        }

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        return [
            'account_id' => (string) $settings->zoom_account_id,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
    }

    /**
     * @param  array{account_id: string, client_id: string, client_secret: string}  $creds
     */
    private function accessToken(array $creds): ?string
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($creds['client_id'], $creds['client_secret'])
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $creds['account_id'],
                ]);

            if (! $response->successful()) {
                Log::warning('zoom.oauth.token', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return (string) ($response->json('access_token') ?? '');
        } catch (Throwable $e) {
            Log::warning('zoom.oauth.token_exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
