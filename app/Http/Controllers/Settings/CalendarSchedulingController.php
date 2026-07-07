<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Models\Company;
use App\Services\CalendlyConfigService;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarSchedulingController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function __construct(
        private CalendlyConfigService $calendly,
        private GoogleCalendarSyncService $googleCalendar
    ) {}

    public function edit(Request $request): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        abort_if(! $company instanceof Company, 403);

        $settings = $company->settings;
        $canManageGoogle = $user->hasRole('company_admin');

        return view('settings.calendar-scheduling', [
            'form' => $this->calendly->toFormArray($company),
            'canManageGoogle' => $canManageGoogle,
            'googleConfigured' => $this->googleCalendar->isConfigured($company),
            'googleConnectedEmail' => $settings?->google_calendar_connected_email,
            'googleConnectedAt' => $settings?->google_calendar_connected_at,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeWorkspaceManagers($request);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);

        $data = $request->validate([
            'booking_url' => ['nullable', 'string', 'max:512'],
            'embed_enabled' => ['nullable', 'boolean'],
        ]);

        $url = isset($data['booking_url']) ? trim((string) $data['booking_url']) : '';
        if ($url !== '' && ! preg_match('#^https://(www\.)?calendly\.com/.+#i', $this->calendly->normalizeBookingUrl($url))) {
            return back()
                ->withInput()
                ->withErrors(['booking_url' => __('calendly_url_invalid')]);
        }

        $this->calendly->save($company, [
            'booking_url' => $url !== '' ? $url : null,
            'embed_enabled' => $request->boolean('embed_enabled'),
        ]);

        return redirect()
            ->route('settings.calendar-scheduling')
            ->with('status', __('calendar_scheduling_saved'));
    }
}
