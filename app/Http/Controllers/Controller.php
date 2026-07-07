<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * CORS for cross-origin embed/widget requests (token is the credential; origin is untrusted).
     */
    protected function withEmbedCors(JsonResponse $response): JsonResponse
    {
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With, X-Flowdesk-Context')
            ->header('Access-Control-Max-Age', '86400');
    }
}
