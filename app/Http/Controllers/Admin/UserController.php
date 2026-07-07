<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', ''));

        $users = User::query()
            ->with(['company'])
            ->with('roles')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($qq) use ($q): void {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($role !== '', function ($query) use ($role): void {
                $query->whereHas('roles', fn ($r) => $r->where('name', $role));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->pluck('name')->all();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'q' => $q,
            'role' => $role,
        ]);
    }

    public function edit(User $user): View
    {
        $user->load(['company', 'roles']);
        $roles = Role::query()->orderBy('name')->pluck('name')->all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $roles = array_values(array_unique(array_filter($data['roles'] ?? [])));

        $user->syncRoles($roles);

        return redirect()->route('admin.users.edit', $user)->with('status', __('User updated.'));
    }
}
