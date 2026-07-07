<?php

namespace App\Http\Controllers;

use App\Models\InstalledModule;
use App\Services\MarketingRegionService;
use App\Services\ModuleRegistry;
use App\Services\ModuleSettingsService;
use App\Services\ModuleTranslationService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulePageController extends Controller
{
    public function show(Request $request, string $slug, ModuleRegistry $registry, ModuleTranslationService $moduleTranslations, ModuleSettingsService $moduleSettings, ?string $page = null): View
    {
        $user = $request->user();
        $company = $user?->company;

        if ($company && $user->hasAnyRole(['company_admin', 'team_member'])) {
            $module = InstalledModule::query()
                ->where('company_id', $company->id)
                ->where('slug', $slug)
                ->where('is_enabled', true)
                ->first();

            if ($module) {
                abort_unless(
                    app(PlanLimitService::class)->isFeatureEnabled($company, 'modules'),
                    403,
                    __('plan_feature_not_included'),
                );

                $viewFile = $registry->resolveViewFile($module, $page);
                $coreView = $viewFile === null ? $registry->coreViewForPage($page) : null;
                abort_if($viewFile === null && $coreView === null, 404);

                $moduleTranslations->register($module);

                $currentPage = trim((string) ($page ?? ''), '/');

                return view('modules.shell', [
                    'module' => $module,
                    'viewFile' => $viewFile,
                    'coreView' => $coreView,
                    'currentPage' => $currentPage,
                    'modulePages' => $module->navigationPages(),
                    'moduleSettings' => $moduleSettings,
                    'novaSuggestUrl' => route('assistant.suggest'),
                    'novaAssistantUrl' => route('assistant.index'),
                ]);
            }
        }

        if ($page !== null) {
            abort(404);
        }

        return app(MarketingController::class)->moduleShow(
            $slug,
            $request,
            app(MarketingRegionService::class),
        );
    }
}
