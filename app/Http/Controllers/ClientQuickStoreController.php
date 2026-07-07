<?php

namespace App\Http\Controllers;

use App\Enums\ClientSource;
use App\Models\Client;
use App\Models\Provider;
use App\Services\ClientCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientQuickStoreController extends Controller
{
    /**
     * Create a minimal client from the project form (staff or same-company business provider).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, 403);

        $companyId = null;
        if ($user->hasAnyRole(['company_admin', 'team_member']) && $user->can('workspace.manage_clients')) {
            $company = $user->company;
            abort_if(! $company, 403);
            $companyId = $company->id;
        } elseif ($user->hasRole('business_provider')) {
            $provider = Provider::query()->withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->first();
            abort_if(! $provider, 403);
            $companyId = $provider->company_id;
        } else {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'source' => ['nullable', 'string', Rule::in(ClientSource::values())],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:120'],
            'address_country' => ['nullable', 'string', 'max:8'],
        ]);

        $address = array_filter([
            'line1' => $data['address_line1'] ?? null,
            'city' => $data['address_city'] ?? null,
            'country' => $data['address_country'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        $address = $address === [] ? null : $address;

        $client = Client::query()->withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? null,
            'address' => $address,
        ]);

        app(ClientCodeService::class)->assignIfMissing($client);

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code,
            ],
        ]);
    }
}
