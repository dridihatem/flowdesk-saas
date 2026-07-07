<?php

namespace App\Services;

use App\Enums\MarketplaceOrderStatus;
use App\Mail\MarketplaceOrderPaidMail;
use App\Models\Company;
use App\Models\CompanyMarketplaceModuleDismissal;
use App\Models\InstalledModule;
use App\Models\MarketplaceModule;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class MarketplaceOrderFulfillmentService
{
    private const MAX_ATTACHMENT_BYTES = 8_388_608;

    public function __construct(
        private ModuleInstallerService $installer,
        private MarketplaceModuleZipService $zipService,
    ) {}

    public function fulfill(MarketplaceOrder $order): void
    {
        $order->loadMissing(['items.module', 'user.company', 'company']);

        $metadata = is_array($order->metadata) ? $order->metadata : [];
        if (! empty($metadata['fulfilled_at'])) {
            return;
        }

        $company = $this->resolveCompany($order);
        if ($company && ! $order->company_id) {
            $order->update(['company_id' => $company->id]);
        }

        $installedNames = $this->installModulesForCompany($order, $company);
        $downloads = $this->buildDownloadPayload($order);

        try {
            Mail::to($order->customer_email)->send(
                new MarketplaceOrderPaidMail($order, $downloads, $installedNames, $company),
            );
        } catch (Throwable $e) {
            Log::warning('marketplace_order_fulfillment_mail_failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        $order->update([
            'metadata' => array_merge($metadata, [
                'fulfilled_at' => now()->toIso8601String(),
                'installed_modules' => $installedNames,
            ]),
        ]);
    }

    /**
     * @return Collection<int, array{item: MarketplaceOrderItem, order: MarketplaceOrder, module: MarketplaceModule|null, is_installed: bool}>
     */
    public function purchasedModulesForCompany(Company $company): Collection
    {
        $installedSlugs = InstalledModule::query()
            ->where('company_id', $company->id)
            ->pluck('slug')
            ->all();

        $dismissedSlugs = CompanyMarketplaceModuleDismissal::query()
            ->where('company_id', $company->id)
            ->pluck('module_slug')
            ->all();

        $items = MarketplaceOrderItem::query()
            ->whereHas('order', fn ($query) => $this->scopePaidOrdersForCompany($query, $company))
            ->with(['module', 'order'])
            ->latest()
            ->get();

        return $items
            ->unique(fn ($item) => (string) ($item->marketplace_module_id ?: $item->module_slug))
            ->reject(fn ($item) => in_array($item->module_slug, $dismissedSlugs, true))
            ->map(function ($item) use ($installedSlugs) {
                $module = $item->module;
                $isInstalled = in_array($item->module_slug, $installedSlugs, true)
                    || ($module && in_array($module->slug, $installedSlugs, true));

                return [
                    'item' => $item,
                    'order' => $item->order,
                    'module' => $module,
                    'is_installed' => $isInstalled,
                ];
            })
            ->values();
    }

    public function companyOwnsPaidModule(Company $company, string $marketplaceModuleId): bool
    {
        return MarketplaceOrderItem::query()
            ->where('marketplace_module_id', $marketplaceModuleId)
            ->whereHas('order', fn ($query) => $this->scopePaidOrdersForCompany($query, $company))
            ->exists();
    }

    public function dismissPurchasedModule(Company $company, string $moduleSlug, ?string $marketplaceModuleId = null): void
    {
        CompanyMarketplaceModuleDismissal::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'module_slug' => $moduleSlug,
            ],
            [
                'marketplace_module_id' => $marketplaceModuleId,
                'dismissed_at' => now(),
            ],
        );
    }

    public function companyOwnsPaidModuleSlug(Company $company, string $moduleSlug): bool
    {
        return MarketplaceOrderItem::query()
            ->where('module_slug', $moduleSlug)
            ->whereHas('order', fn ($query) => $this->scopePaidOrdersForCompany($query, $company))
            ->exists();
    }

    /**
     * @param  Builder<MarketplaceOrder>  $query
     */
    private function scopePaidOrdersForCompany($query, Company $company): void
    {
        $companyUserIds = User::query()
            ->where('company_id', $company->id)
            ->pluck('id')
            ->all();

        $companyEmails = User::query()
            ->where('company_id', $company->id)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $query->where('status', MarketplaceOrderStatus::Paid)
            ->where(function ($inner) use ($company, $companyUserIds, $companyEmails): void {
                $inner->where('company_id', $company->id);

                if ($companyUserIds !== []) {
                    $inner->orWhereIn('user_id', $companyUserIds);
                }

                if ($companyEmails !== []) {
                    $inner->orWhereIn('customer_email', $companyEmails);
                }
            });
    }

    private function resolveCompany(MarketplaceOrder $order): ?Company
    {
        if ($order->company) {
            return $order->company;
        }

        if ($order->user?->company) {
            return $order->user->company;
        }

        $user = User::query()
            ->where('email', $order->customer_email)
            ->whereNotNull('company_id')
            ->first();

        return $user?->company;
    }

    /**
     * @return list<string>
     */
    private function installModulesForCompany(MarketplaceOrder $order, ?Company $company): array
    {
        if (! $company) {
            return [];
        }

        $installed = [];

        foreach ($order->items as $item) {
            $module = $item->module;
            if (! $module) {
                continue;
            }

            $zipPath = $this->zipService->absolutePath($module);
            if ($zipPath === null) {
                continue;
            }

            if (InstalledModule::query()
                ->where('company_id', $company->id)
                ->where(function ($query) use ($module, $item): void {
                    $query->where('slug', $item->module_slug)
                        ->orWhere('slug', $module->slug);
                })
                ->exists()) {
                continue;
            }

            try {
                $this->installer->installFromStoredZip($company, $zipPath);
                $installed[] = $module->name;
            } catch (Throwable $e) {
                Log::warning('marketplace_order_auto_install_failed', [
                    'order_id' => $order->id,
                    'module_id' => $module->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $installed;
    }

    /**
     * @return list<array{name: string, url: ?string, attach_path: ?string, attach_name: ?string, size: int}>
     */
    private function buildDownloadPayload(MarketplaceOrder $order): array
    {
        $downloads = [];
        $attachBudget = self::MAX_ATTACHMENT_BYTES;

        foreach ($order->items as $item) {
            $module = $item->module;
            $zipPath = $module ? $this->zipService->absolutePath($module) : null;
            $size = ($zipPath && is_file($zipPath)) ? (int) filesize($zipPath) : 0;

            $attachPath = null;
            $attachName = null;
            if ($zipPath && $size > 0 && $size <= $attachBudget) {
                $attachPath = $zipPath;
                $attachName = ($module->slug ?? $item->module_slug).'.zip';
                $attachBudget -= $size;
            }

            $url = null;
            if ($module && $zipPath) {
                $url = URL::temporarySignedRoute(
                    'marketing.order.download',
                    now()->addDays(7),
                    ['order' => $order->id, 'module' => $module->id],
                );
            }

            $downloads[] = [
                'name' => $item->module_name,
                'url' => $url,
                'attach_path' => $attachPath,
                'attach_name' => $attachName,
                'size' => $size,
            ];
        }

        return $downloads;
    }
}
