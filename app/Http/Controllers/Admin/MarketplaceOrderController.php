<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MarketplaceOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceOrder;
use App\Services\MarketplaceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        if ($status !== '' && MarketplaceOrderStatus::tryFrom($status) === null) {
            $status = '';
        }

        $reference = trim((string) $request->query('reference', ''));

        $orders = MarketplaceOrder::query()
            ->withCount('items')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($reference !== '', function ($query) use ($reference): void {
                $query->where(function ($inner) use ($reference): void {
                    $inner->where('payment_reference', 'like', '%'.$reference.'%')
                        ->orWhere('order_number', 'like', '%'.$reference.'%');
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.marketplace-orders.index', [
            'orders' => $orders,
            'selectedStatus' => $status,
            'selectedReference' => $reference,
            'statuses' => MarketplaceOrderStatus::cases(),
        ]);
    }

    public function show(MarketplaceOrder $marketplaceOrder): View
    {
        $marketplaceOrder->load(['items.module', 'user', 'company']);

        return view('admin.marketplace-orders.show', [
            'order' => $marketplaceOrder,
        ]);
    }

    public function updateStatus(Request $request, MarketplaceOrder $marketplaceOrder, MarketplaceCheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(MarketplaceOrderStatus::class)],
        ]);

        $status = MarketplaceOrderStatus::from($data['status']);

        if ($status === MarketplaceOrderStatus::Paid) {
            $checkout->markPaid($marketplaceOrder);
        } elseif ($status === MarketplaceOrderStatus::Cancelled) {
            $checkout->cancelOrder($marketplaceOrder);
        } else {
            $marketplaceOrder->update(['status' => $status]);
        }

        return redirect()
            ->route('admin.marketplace-orders.show', $marketplaceOrder)
            ->with('status', __('admin_marketplace_order_status_updated'));
    }
}
