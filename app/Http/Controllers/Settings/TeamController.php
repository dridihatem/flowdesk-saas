<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        abort_if(! $actor->hasRole('company_admin'), 403);
        $company = $actor->company;
        abort_if(! $company instanceof Company, 403);

        $users = User::query()
            ->where('company_id', $company->id)
            ->workspaceStaff()
            ->orderBy('name')
            ->with('roles')
            ->get();

        $adminCount = User::query()
            ->where('company_id', $company->id)
            ->workspaceStaff()
            ->whereHas('roles', fn ($q) => $q->where('name', 'company_admin'))
            ->count();

        return view('settings.team', [
            'users' => $users,
            'adminCount' => $adminCount,
            'roleOptions' => [
                'company_admin' => __('Company admin'),
                'team_member' => __('Team member'),
                'business_provider' => __('Business provider'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_if(! $actor->hasRole('company_admin'), 403);
        $company = $actor->company;
        abort_if(! $company instanceof Company, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['company_admin', 'team_member', 'business_provider'])],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('settings.team')->with('status', __('Team member added.'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_if(! $actor->hasRole('company_admin'), 403);
        $this->assertSameCompany($actor, $user);
        abort_if($user->hasRole('client'), 404);

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in(['company_admin', 'team_member', 'business_provider'])],
        ]);

        $company = $actor->company;
        abort_if(! $company instanceof Company, 403);

        if ($user->hasRole('company_admin') && $data['role'] !== 'company_admin') {
            $adminCount = User::query()
                ->where('company_id', $company->id)
                ->workspaceStaff()
                ->whereHas('roles', fn ($q) => $q->where('name', 'company_admin'))
                ->count();
            if ($adminCount <= 1) {
                return redirect()->route('settings.team')->withErrors([
                    'role' => __('The workspace must keep at least one company admin.'),
                ]);
            }
        }

        $user->syncRoles([$data['role']]);

        return redirect()->route('settings.team')->with('status', __('Role updated.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_if(! $actor->hasRole('company_admin'), 403);
        $this->assertSameCompany($actor, $user);
        abort_if($user->hasRole('client'), 404);

        if ($user->id === $actor->id) {
            return redirect()->route('settings.team')->withErrors([
                'delete' => __('You cannot remove your own account from here.'),
            ]);
        }

        $company = $actor->company;
        abort_if(! $company instanceof Company, 403);

        if ($user->hasRole('company_admin')) {
            $adminCount = User::query()
                ->where('company_id', $company->id)
                ->workspaceStaff()
                ->whereHas('roles', fn ($q) => $q->where('name', 'company_admin'))
                ->count();
            if ($adminCount <= 1) {
                return redirect()->route('settings.team')->withErrors([
                    'delete' => __('You cannot remove the only company admin.'),
                ]);
            }
        }

        $user->delete();

        return redirect()->route('settings.team')->with('status', __('User removed from workspace.'));
    }

    private function assertSameCompany(User $actor, User $target): void
    {
        if ($target->company_id === null || $actor->company_id === null) {
            abort(404);
        }
        if ((string) $target->company_id !== (string) $actor->company_id) {
            abort(404);
        }
    }
}
