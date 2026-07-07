<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\InstalledModule;
use App\Services\ModuleCalendarSyncService;
use App\Services\ModuleSettingsService;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleActionController extends Controller
{
    public function handle(
        Request $request,
        string $slug,
        ModuleSettingsService $moduleSettings,
        ModuleCalendarSyncService $calendarSync,
        PlanLimitService $planLimits,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole(['company_admin', 'team_member']), 403);

        $company = $user->company;
        abort_if(! $company instanceof Company, 403);

        abort_unless($planLimits->isFeatureEnabled($company, 'modules'), 403, __('plan_feature_not_included'));

        $module = InstalledModule::query()
            ->where('company_id', $company->id)
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->firstOrFail();

        $action = (string) $request->input('module_action', '');
        $page = trim((string) $request->input('return_page', ''), '/');

        $redirect = $page === ''
            ? redirect()->route('modules.show', $slug)
            : redirect()->route('modules.show', ['slug' => $slug, 'page' => $page]);

        return match ($action) {
            'save_integrations' => $this->saveIntegrations($request, $module, $moduleSettings, $redirect, $user),
            'store_viewing', 'update_viewing', 'delete_viewing' => $this->handleViewingAction(
                $request,
                $module,
                $company,
                $user,
                $action,
                $moduleSettings,
                $calendarSync,
                $redirect,
            ),
            default => $redirect->with('error', __('modules_action_unknown')),
        };
    }

    private function saveIntegrations(
        Request $request,
        InstalledModule $module,
        ModuleSettingsService $moduleSettings,
        RedirectResponse $redirect,
        $user,
    ): RedirectResponse {
        abort_unless($user->hasRole('company_admin'), 403);

        $toggles = $request->input('integrations', []);
        if (! is_array($toggles)) {
            $toggles = [];
        }

        $moduleSettings->saveIntegrations($module, $toggles);

        return $redirect->with('status', __('module_settings_saved'));
    }

    private function handleViewingAction(
        Request $request,
        InstalledModule $module,
        Company $company,
        $user,
        string $action,
        ModuleSettingsService $moduleSettings,
        ModuleCalendarSyncService $calendarSync,
        RedirectResponse $redirect,
    ): RedirectResponse {
        if (! $this->moduleHasViewings($module)) {
            return $redirect->with('error', __('modules_action_unknown'));
        }

        if ($action === 'delete_viewing') {
            return $this->deleteViewing($request, $company, $calendarSync, $redirect);
        }

        $validated = $request->validate([
            'property_title' => ['required', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:120'],
            'scheduled_at' => ['nullable', 'date'],
            'client_id' => ['nullable', 'string', 'max:26'],
            'viewing_id' => ['nullable', 'string', 'max:26'],
        ]);

        $clientId = $validated['client_id'] ?? null;
        if ($clientId !== null && $clientId !== '') {
            abort_unless(
                DB::table('clients')->where('company_id', $company->id)->where('id', $clientId)->exists(),
                422,
            );
        } else {
            $clientId = null;
        }

        $clientName = $clientId
            ? DB::table('clients')->where('id', $clientId)->value('name')
            : null;

        $calendarEnabled = $moduleSettings->isIntegrationEnabled($module, $company, 'calendar');

        if ($action === 'update_viewing') {
            $viewingId = (string) ($validated['viewing_id'] ?? '');
            abort_if($viewingId === '', 422);

            $exists = DB::table('module_property_viewings')
                ->where('company_id', $company->id)
                ->where('id', $viewingId)
                ->exists();
            abort_unless($exists, 404);

            DB::table('module_property_viewings')
                ->where('company_id', $company->id)
                ->where('id', $viewingId)
                ->update([
                    'property_title' => $validated['property_title'],
                    'zone' => ($validated['zone'] ?? null) ?: null,
                    'scheduled_at' => $validated['scheduled_at'] ?? null,
                    'client_id' => $clientId,
                    'updated_at' => now(),
                ]);

            if ($calendarEnabled) {
                $calendarSync->syncPropertyViewing(
                    $company,
                    $user,
                    $viewingId,
                    $validated['property_title'],
                    $validated['zone'] ?? null,
                    $validated['scheduled_at'] ?? null,
                    is_string($clientName) ? $clientName : null,
                    $module->slug,
                );
            } else {
                $calendarSync->detachViewingCalendarEvent($company, $viewingId);
            }

            return $redirect->with('status', __('module_viewing_updated'));
        }

        $viewingId = (string) Str::ulid();

        DB::table('module_property_viewings')->insert([
            'id' => $viewingId,
            'company_id' => $company->id,
            'client_id' => $clientId,
            'property_title' => $validated['property_title'],
            'zone' => ($validated['zone'] ?? null) ?: null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($calendarEnabled && filled($validated['scheduled_at'] ?? null)) {
            $calendarSync->syncPropertyViewing(
                $company,
                $user,
                $viewingId,
                $validated['property_title'],
                $validated['zone'] ?? null,
                $validated['scheduled_at'] ?? null,
                is_string($clientName) ? $clientName : null,
                $module->slug,
            );
        }

        return $redirect->with('status', __('module_viewing_saved'));
    }

    private function deleteViewing(
        Request $request,
        Company $company,
        ModuleCalendarSyncService $calendarSync,
        RedirectResponse $redirect,
    ): RedirectResponse {
        $viewingId = (string) $request->input('viewing_id', '');
        abort_if($viewingId === '', 422);

        $exists = DB::table('module_property_viewings')
            ->where('company_id', $company->id)
            ->where('id', $viewingId)
            ->exists();
        abort_unless($exists, 404);

        $calendarSync->detachViewingCalendarEvent($company, $viewingId);

        DB::table('module_property_viewings')
            ->where('company_id', $company->id)
            ->where('id', $viewingId)
            ->delete();

        return $redirect->with('status', __('module_viewing_deleted'));
    }

    private function moduleHasViewings(InstalledModule $module): bool
    {
        if ($module->slug === 'qatar-real-estate' || $module->slug === 'qatar-property-viewings') {
            return true;
        }

        $pages = collect($module->manifest['pages'] ?? [])->pluck('slug');

        return $pages->contains('viewings');
    }
}
