<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Enums\ClientSource;
use App\Http\Controllers\Api\Concerns\ResolvesApiWorkspace;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientApiController extends Controller
{
    use ResolvesApiWorkspace;

    public function index(Request $request): JsonResponse
    {
        $company = $this->apiCompany();
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $query = Client::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->latest();

        if ($request->filled('q')) {
            $q = $request->string('q')->trim()->toString();
            $query->where(function ($sub) use ($q): void {
                $sub->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%');
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Client $c) => $this->clientPayload($c))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Client $client): JsonResponse
    {
        $company = $this->apiCompany();
        abort_if((string) $client->company_id !== (string) $company->id, 404);

        return response()->json(['data' => $this->clientPayload($client)]);
    }

    public function store(Request $request, ClientCodeService $clientCodes): JsonResponse
    {
        $company = $this->apiCompany();

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
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? ClientSource::Other->value,
            'address' => $address,
        ]);

        $clientCodes->assignIfMissing($client);

        return response()->json(['data' => $this->clientPayload($client->fresh())], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function clientPayload(Client $client): array
    {
        $address = is_array($client->address) ? $client->address : [];

        return [
            'id' => $client->id,
            'code' => $client->code,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'source' => $client->source instanceof ClientSource ? $client->source->value : $client->source,
            'address' => [
                'line1' => $address['line1'] ?? null,
                'city' => $address['city'] ?? null,
                'country' => $address['country'] ?? null,
            ],
            'created_at' => $client->created_at?->toIso8601String(),
        ];
    }
}
