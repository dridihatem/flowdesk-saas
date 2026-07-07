<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CalendlyConfigService;
use App\Services\GoogleCalendarSyncService;
use App\Services\WorkspaceCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        private WorkspaceCalendarService $calendar,
        private CalendlyConfigService $calendly,
        private GoogleCalendarSyncService $googleCalendar,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->can('workspace.view_dashboard'), 403);

        $company = $user->company;
        abort_if(! $company instanceof Company, 403);

        [$from, $to, $month] = $this->resolveRange($request);
        $events = $this->calendar->eventsForCompany($company, $from, $to);
        $calendly = $this->calendly->get($company);

        return view('calendar.index', [
            'events' => $events,
            'month' => $month,
            'calendly' => $calendly,
            'isPortal' => false,
            'googleCalendarConnected' => $this->googleCalendar->isConnected($company),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('workspace.view_dashboard'), 403);

        $company = $user->company;
        abort_if(! $company instanceof Company, 403);

        $month = $request->query('month');
        $monthParam = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) ? $month : null;

        return response()->json($this->calendar->navPreview($company, null, false, $monthParam));
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $monthParam = $request->query('month');
        $anchor = is_string($monthParam) && preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $from = $anchor->copy()->subMonth()->startOfMonth();
        $to = $anchor->copy()->addMonth()->endOfMonth();

        return [$from, $to, $anchor->format('Y-m')];
    }
}
