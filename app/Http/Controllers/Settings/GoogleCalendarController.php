<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Jobs\SyncProjectToGoogleCalendarJob;
use App\Models\Project;
use App\Services\GoogleCalendarSyncService;
use App\Services\ZoomMeetingService;
use Google\Service\Calendar as GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleCalendarController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function edit(Request $request, GoogleCalendarSyncService $sync, ZoomMeetingService $zoom): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        $settings = $company->settings;
        $canManage = $user->hasRole('company_admin');

        return view('settings.google-calendar', [
            'canManage' => $canManage,
            'connectedEmail' => $settings?->google_calendar_connected_email,
            'connectedAt' => $settings?->google_calendar_connected_at,
            'isConfigured' => $sync->isConfigured($company),
            'zoomConfigured' => $zoom->isConfigured($company),
            'zoomAccountId' => $settings?->zoom_account_id,
            'hasZoomClientId' => filled($settings?->zoom_client_id_encrypted),
            'hasZoomClientSecret' => filled($settings?->zoom_client_secret_encrypted),
        ]);
    }

    public function redirect(Request $request): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_unless($user->hasRole('company_admin'), 403);

        $calRedirect = trim((string) config('services.google.calendar_redirect'));
        if ($calRedirect === '') {
            return redirect()
                ->route('settings.google-calendar')
                ->withErrors(['google' => __('Google Calendar redirect URI is not configured.')]);
        }

        session(['google_calendar_oauth_company_id' => (string) $user->company_id]);

        return Socialite::driver('google')
            ->redirectUrl($calRedirect)
            ->scopes([
                'openid',
                'email',
                'profile',
                GoogleCalendarService::CALENDAR_EVENTS,
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request, GoogleCalendarSyncService $sync): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user?->hasRole('company_admin'), 403);

        $expected = session('google_calendar_oauth_company_id');
        session()->forget('google_calendar_oauth_company_id');
        if ($expected === null || (string) $user->company_id !== (string) $expected) {
            return redirect()
                ->route('settings.google-calendar')
                ->withErrors(['google' => __('Session expired. Try connecting again.')]);
        }

        $calRedirect = trim((string) config('services.google.calendar_redirect'));
        if ($calRedirect === '') {
            return redirect()
                ->route('settings.google-calendar')
                ->withErrors(['google' => __('Google Calendar redirect URI is not configured.')]);
        }

        try {
            $social = Socialite::driver('google')
                ->redirectUrl($calRedirect)
                ->user();
        } catch (Throwable $e) {
            Log::warning('google.calendar.oauth', ['message' => $e->getMessage()]);

            return redirect()
                ->route('settings.google-calendar')
                ->withErrors(['google' => __('Could not connect to Google. Try again or check your Google Cloud OAuth settings.')]);
        }

        $refresh = $social->refreshToken ?? null;
        if (! is_string($refresh) || $refresh === '') {
            return redirect()
                ->route('settings.google-calendar')
                ->withErrors(['google' => __('No refresh token from Google. Revoke the app in your Google account security settings, then try again with consent.')]);
        }

        $company = $user->company;
        if (! $company) {
            abort(403);
        }

        $sync->saveRefreshToken($company, $refresh, $social->getEmail());

        Project::query()
            ->where('company_id', $company->id)
            ->pluck('id')
            ->each(fn (string $id) => SyncProjectToGoogleCalendarJob::dispatch($id));

        return redirect()
            ->route('settings.google-calendar')
            ->with('status', __('Google Calendar connected.'));
    }

    public function disconnect(Request $request, GoogleCalendarSyncService $sync): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_unless($user->hasRole('company_admin'), 403);
        $company = $user->company;
        if (! $company) {
            abort(403);
        }
        $sync->disconnect($company);

        return redirect()
            ->route('settings.google-calendar')
            ->with('status', __('Google Calendar disconnected.'));
    }

    public function updateZoom(Request $request, ZoomMeetingService $zoom): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_unless($user->hasRole('company_admin'), 403);
        $company = $user->company;
        abort_if(! $company, 403);

        $data = $request->validate([
            'zoom_account_id' => ['nullable', 'string', 'max:120'],
            'zoom_client_id' => ['nullable', 'string', 'max:255'],
            'zoom_client_secret' => ['nullable', 'string', 'max:255'],
            'clear_zoom' => ['nullable', 'boolean'],
        ]);

        $settings = $company->settings()->firstOrCreate();

        if (! empty($data['clear_zoom'])) {
            $settings->update([
                'zoom_account_id' => null,
                'zoom_client_id_encrypted' => null,
                'zoom_client_secret_encrypted' => null,
            ]);

            return redirect()
                ->route('settings.google-calendar')
                ->with('status', __('zoom_settings_cleared'));
        }

        $payload = [
            'zoom_account_id' => filled($data['zoom_account_id'] ?? null) ? $data['zoom_account_id'] : $settings->zoom_account_id,
        ];

        if (filled($data['zoom_client_id'] ?? null)) {
            $payload['zoom_client_id_encrypted'] = $data['zoom_client_id'];
        }

        if (filled($data['zoom_client_secret'] ?? null)) {
            $payload['zoom_client_secret_encrypted'] = $data['zoom_client_secret'];
        }

        $settings->update($payload);

        return redirect()
            ->route('settings.google-calendar')
            ->with('status', __('zoom_settings_saved'));
    }
}
