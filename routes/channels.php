<?php

use App\Models\Nova\NovaRun;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Nova agent activity stream — tenant + run ownership required.
 */
Broadcast::channel('nova.run.{runId}', function ($user, string $runId) {
    if ($user === null || $user->company_id === null) {
        return false;
    }

    $run = NovaRun::query()
        ->withoutGlobalScopes()
        ->whereKey($runId)
        ->first();

    if ($run === null) {
        return false;
    }

    return (string) $run->company_id === (string) $user->company_id
        && (int) $run->user_id === (int) $user->id;
});

/**
 * Company-scoped Nova channel (activity + optional TTS status).
 */
Broadcast::channel('nova.company.{companyId}', function ($user, string $companyId) {
    return $user !== null
        && $user->company_id !== null
        && (string) $user->company_id === (string) $companyId;
});
