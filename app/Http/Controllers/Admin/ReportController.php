<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, PlatformReportService $reports): View
    {
        $from = $request->date('from') ?? Carbon::now()->subDays(30)->startOfDay();
        $to = $request->date('to') ?? Carbon::now()->endOfDay();

        $snapshot = $reports->snapshotAllTime();
        $period = $reports->snapshotPeriod($from, $to);
        $companies = $reports->companiesTable($from, $to, 25);

        return view('admin.reports.index', compact(
            'snapshot',
            'period',
            'companies',
            'from',
            'to',
        ));
    }
}
