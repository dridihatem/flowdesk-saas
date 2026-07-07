<?php

use App\Http\Controllers\Api\EmbedFormController;
use App\Http\Controllers\Api\EmbedTrackController;
use App\Http\Controllers\Api\Workspace\ClientApiController;
use App\Http\Controllers\Api\Workspace\ImportApiController;
use App\Http\Controllers\Api\Workspace\InvoiceApiController;
use App\Http\Controllers\Api\Workspace\ProjectApiController;
use App\Http\Controllers\Api\Workspace\WorkspaceMeController;
use App\Http\Middleware\AuthenticateCompanyApiToken;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:120,1'])->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware([ResolveTenant::class, 'throttle:180,1'])
    ->prefix('v1/embed')
    ->group(function () {
        Route::options('track', [EmbedTrackController::class, 'options']);
        Route::options('forms/{form}', [EmbedFormController::class, 'optionsShow']);
        Route::options('forms/{form}/submissions', [EmbedFormController::class, 'optionsStore']);
    });

Route::middleware([ResolveTenant::class, AuthenticateCompanyApiToken::class, 'throttle:180,1'])
    ->prefix('v1/embed')
    ->group(function () {
        Route::post('track', [EmbedTrackController::class, 'store']);
        Route::get('forms/{form}', [EmbedFormController::class, 'show']);
        Route::post('forms/{form}/submissions', [EmbedFormController::class, 'store']);
    });

Route::middleware([ResolveTenant::class, AuthenticateCompanyApiToken::class, 'throttle:120,1'])
    ->prefix('v1/workspace')
    ->group(function () {
        Route::get('/', WorkspaceMeController::class);
        Route::get('clients', [ClientApiController::class, 'index']);
        Route::post('clients', [ClientApiController::class, 'store']);
        Route::get('clients/{client}', [ClientApiController::class, 'show']);
        Route::get('projects', [ProjectApiController::class, 'index']);
        Route::post('projects', [ProjectApiController::class, 'store']);
        Route::get('projects/{project}', [ProjectApiController::class, 'show']);
        Route::get('invoices', [InvoiceApiController::class, 'index']);
        Route::post('invoices', [InvoiceApiController::class, 'store']);
        Route::get('invoices/{invoice}', [InvoiceApiController::class, 'show']);
        Route::post('import', ImportApiController::class);
    });
