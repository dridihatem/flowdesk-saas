<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WidgetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmbedTrackController extends Controller
{
    /**
     * Record a lightweight page view from the marketing tracker script (company website).
     */
    public function store(Request $request): JsonResponse
    {
        $company = app()->bound('currentCompany') ? app('currentCompany') : null;
        abort_if($company === null, 404, __('Tenant not found.'));

        $data = $request->validate([
            'page_url' => ['required', 'string', 'max:2048'],
            'path' => ['nullable', 'string', 'max:1024'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $data['path'] ?? null;
        if ($path === null || $path === '') {
            $path = parse_url($data['page_url'], PHP_URL_PATH);
            $path = is_string($path) && $path !== '' ? $path : '/';
        }

        WidgetEvent::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'form_id' => null,
            'event' => 'pageview',
            'ip_address' => $request->ip(),
            'context' => [
                'page_url' => $data['page_url'],
                'path' => $path,
                'referrer' => $data['referrer'] ?? null,
                'title' => $data['title'] ?? null,
            ],
        ]);

        return response()->json(['ok' => true])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With, X-Flowdesk-Context');
    }

    public function options(): JsonResponse
    {
        return response()->json(null, 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With, X-Flowdesk-Context')
            ->header('Access-Control-Max-Age', '86400');
    }
}
