<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Api\Concerns\ResolvesApiWorkspace;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class WorkspaceMeController extends Controller
{
    use ResolvesApiWorkspace;

    public function __invoke(): JsonResponse
    {
        $company = $this->apiCompany();

        return response()->json([
            'workspace' => [
                'id' => $company->id,
                'name' => $company->name,
                'subdomain' => $company->subdomain,
                'default_currency' => $company->default_currency,
                'default_locale' => $company->default_locale,
            ],
            'api_version' => 'v1',
        ]);
    }
}
