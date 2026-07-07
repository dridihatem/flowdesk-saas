<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Api\Concerns\ResolvesApiWorkspace;
use App\Http\Controllers\Controller;
use App\Services\WorkspaceApiImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportApiController extends Controller
{
    use ResolvesApiWorkspace;

    public function __invoke(Request $request, WorkspaceApiImportService $import): JsonResponse
    {
        $company = $this->apiCompany();

        $data = $request->validate([
            'clients' => ['nullable', 'array', 'max:100'],
            'clients.*.name' => ['required', 'string', 'max:255'],
            'clients.*.email' => ['nullable', 'string', 'email', 'max:255'],
            'clients.*.phone' => ['nullable', 'string', 'max:64'],
            'clients.*.ref' => ['nullable', 'string', 'max:64'],
            'projects' => ['nullable', 'array', 'max:100'],
            'projects.*.title' => ['required', 'string', 'max:255'],
            'projects.*.description' => ['nullable', 'string', 'max:50000'],
            'projects.*.client_id' => ['nullable', 'string'],
            'projects.*.client_ref' => ['nullable', 'string', 'max:64'],
            'projects.*.status' => ['nullable', 'string'],
        ]);

        if (($data['clients'] ?? []) === [] && ($data['projects'] ?? []) === []) {
            return response()->json(['message' => __('Provide at least one client or project to import.')], 422);
        }

        $result = $import->import($company, $data);

        return response()->json($result, 201);
    }
}
