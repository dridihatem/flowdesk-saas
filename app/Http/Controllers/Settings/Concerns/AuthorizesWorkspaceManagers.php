<?php

namespace App\Http\Controllers\Settings\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait AuthorizesWorkspaceManagers
{
    protected function authorizeWorkspaceManagers(Request $request): User
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 403);
        abort_if(! $user->company, 403);
        abort_if(! $user->hasAnyRole(['company_admin', 'team_member']), 403);

        return $user;
    }
}
