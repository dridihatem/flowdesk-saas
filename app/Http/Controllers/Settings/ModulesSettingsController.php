<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\AuthorizesWorkspaceManagers;
use App\Models\InstalledModule;
use App\Models\MarketplaceModule;
use App\Models\MarketplaceOrderItem;
use App\Services\MarketplaceModuleZipService;
use App\Services\MarketplaceOrderFulfillmentService;
use App\Services\ModuleInstallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModulesSettingsController extends Controller
{
    use AuthorizesWorkspaceManagers;

    public function index(Request $request, MarketplaceOrderFulfillmentService $fulfillment): View
    {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;

        $modules = InstalledModule::query()
            ->orderBy('name')
            ->get();

        $purchasedModules = $company
            ? $fulfillment->purchasedModulesForCompany($company)
            : collect();

        return view('settings.modules', [
            'modules' => $modules,
            'purchasedModules' => $purchasedModules,
            'canManage' => $user->hasRole('company_admin'),
        ]);
    }

    public function install(Request $request, ModuleInstallerService $installer): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_if(! $user->hasRole('company_admin'), 403);

        $company = $user->company;
        abort_if(! $company, 403);

        $request->validate([
            'module_zip' => ['required', 'file', 'mimes:zip', 'max:15360'],
        ]);

        try {
            $installer->installFromZip($company, $request->file('module_zip'));
        } catch (RuntimeException $e) {
            return redirect()
                ->route('settings.modules')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.modules')
            ->with('status', __('modules_installed_ok'));
    }

    public function installPurchased(
        Request $request,
        MarketplaceModule $marketplaceModule,
        MarketplaceOrderFulfillmentService $fulfillment,
        MarketplaceModuleZipService $zipService,
        ModuleInstallerService $installer,
    ): RedirectResponse {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_if(! $user->hasRole('company_admin'), 403);

        $company = $user->company;
        abort_if(! $company, 403);

        abort_unless($fulfillment->companyOwnsPaidModule($company, $marketplaceModule->id), 403);

        $zipPath = $zipService->absolutePath($marketplaceModule);
        if ($zipPath === null) {
            return redirect()
                ->route('settings.modules')
                ->with('error', __('marketing_checkout_no_zip'));
        }

        try {
            $installer->installFromStoredZip($company, $zipPath);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('settings.modules')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.modules')
            ->with('status', __('settings_modules_purchased_installed', ['name' => $marketplaceModule->name]));
    }

    public function downloadPurchased(
        Request $request,
        MarketplaceModule $marketplaceModule,
        MarketplaceOrderFulfillmentService $fulfillment,
        MarketplaceModuleZipService $zipService,
    ): StreamedResponse {
        $user = $this->authorizeWorkspaceManagers($request);
        $company = $user->company;
        abort_if(! $company, 403);

        abort_unless($fulfillment->companyOwnsPaidModule($company, $marketplaceModule->id), 403);

        $zipPath = $zipService->absolutePath($marketplaceModule);
        abort_if($zipPath === null || ! is_file($zipPath), 404);

        $filename = $marketplaceModule->slug.'.zip';

        return response()->streamDownload(
            fn () => readfile($zipPath),
            $filename,
            ['Content-Type' => 'application/zip'],
        );
    }

    public function destroyPurchased(
        Request $request,
        MarketplaceOrderItem $marketplaceOrderItem,
        MarketplaceOrderFulfillmentService $fulfillment,
        ModuleInstallerService $installer,
    ): RedirectResponse {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_if(! $user->hasRole('company_admin'), 403);

        $company = $user->company;
        abort_if(! $company, 403);

        $marketplaceOrderItem->loadMissing('order');
        $order = $marketplaceOrderItem->order;
        abort_if($order === null, 404);

        abort_unless(
            $fulfillment->companyOwnsPaidModuleSlug($company, $marketplaceOrderItem->module_slug),
            403,
        );

        $installed = InstalledModule::query()
            ->where('company_id', $company->id)
            ->where('slug', $marketplaceOrderItem->module_slug)
            ->first();

        if ($installed) {
            $installer->uninstall($installed);
        }

        $fulfillment->dismissPurchasedModule(
            $company,
            $marketplaceOrderItem->module_slug,
            $marketplaceOrderItem->marketplace_module_id,
        );

        return redirect()
            ->route('settings.modules')
            ->with('status', __('settings_modules_purchased_removed', ['name' => $marketplaceOrderItem->module_name]));
    }

    public function toggle(Request $request, InstalledModule $module, ModuleInstallerService $installer): RedirectResponse
    {
        $this->authorizeWorkspaceManagers($request);
        abort_if((string) $module->company_id !== (string) $request->user()->company_id, 403);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $installer->setEnabled($module, (bool) $data['enabled']);

        return redirect()
            ->route('settings.modules')
            ->with('status', __('modules_status_updated'));
    }

    public function destroy(Request $request, InstalledModule $module, ModuleInstallerService $installer): RedirectResponse
    {
        $user = $this->authorizeWorkspaceManagers($request);
        abort_if(! $user->hasRole('company_admin'), 403);
        abort_if((string) $module->company_id !== (string) $user->company_id, 403);

        $installer->uninstall($module);

        return redirect()
            ->route('settings.modules')
            ->with('status', __('modules_uninstalled_ok'));
    }
}
