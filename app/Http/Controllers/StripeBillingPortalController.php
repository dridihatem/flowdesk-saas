<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Customer;
use Stripe\Stripe;

class StripeBillingPortalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->hasRole('company_admin'), 403);
        $company = $user->company;
        abort_if(! $company, 403);

        $secret = config('services.stripe.secret');
        if ($secret === null || $secret === '') {
            return redirect()->route('billing.index')->withErrors([
                'stripe' => __('Set STRIPE_SECRET in your environment to open the Stripe Customer Portal.'),
            ]);
        }

        Stripe::setApiKey($secret);

        if ($company->stripe_customer_id === null || $company->stripe_customer_id === '') {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $company->name,
                'metadata' => ['company_id' => $company->id],
            ]);
            $company->update(['stripe_customer_id' => $customer->id]);
            $company->refresh();
        }

        $session = BillingPortalSession::create([
            'customer' => $company->stripe_customer_id,
            'return_url' => route('billing.index', absolute: true),
        ]);

        return redirect()->away($session->url);
    }
}
