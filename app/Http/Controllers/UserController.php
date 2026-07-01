<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List users (search, filter by role).
     */
    public function index(Request $request): View
    {
        $query = User::query()->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('q')) {
            $q = '%' . addcslashes($request->q, '%_\\') . '%';
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', $q)
                    ->orWhere('email', 'like', $q)
                    ->orWhere('first_name', 'like', $q)
                    ->orWhere('last_name', 'like', $q);
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Show create user form.
     */
    public function create(): View
    {
        $roles = $this->assignableRoles();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $roles = array_keys($this->assignableRoles());
        $request->validate([
            'first_name' => ValidationRules::personName(),
            'middle_name' => ValidationRules::personName(false),
            'last_name' => ValidationRules::personName(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:'.implode(',', $roles)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], ValidationRules::requiredMessages(), [
            'first_name' => __('first name'),
            'middle_name' => __('middle name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'role' => __('role'),
            'password' => __('password'),
        ]);

        $name = trim($request->first_name . ' ' . ($request->middle_name ?? '') . ' ' . $request->last_name);

        User::create([
            'name' => $name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.index')->with('status', __('User created successfully.'));
    }

    /**
     * Show edit user form.
     */
    public function edit(User $user): View|RedirectResponse
    {
        if (! $this->canManageUser($user)) {
            return redirect()->route('users.index')->with('error', __('You cannot edit this user.'));
        }
        $roles = $this->assignableRoles();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $this->canManageUser($user)) {
            return redirect()->route('users.index')->with('error', __('You cannot edit this user.'));
        }

        $roles = array_keys($this->assignableRoles());
        $rules = [
            'first_name' => ValidationRules::personName(),
            'middle_name' => ValidationRules::personName(false),
            'last_name' => ValidationRules::personName(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string', 'in:'.implode(',', $roles)],
        ];
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }
        $request->validate($rules, ValidationRules::requiredMessages(), [
            'first_name' => __('first name'),
            'middle_name' => __('middle name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'role' => __('role'),
            'password' => __('password'),
        ]);

        $name = trim($request->first_name . ' ' . ($request->middle_name ?? '') . ' ' . $request->last_name);

        $data = [
            'name' => $name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role' => $request->role,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('status', __('User updated successfully.'));
    }

    /**
     * Delete user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', __('You cannot delete your own account.'));
        }
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return redirect()->route('users.index')->with('error', __('Cannot delete the last Super Admin.'));
        }
        if (auth()->user()->role === 'admin' && in_array($user->role, ['super_admin', 'admin'], true)) {
            return redirect()->route('users.index')->with('error', __('You cannot delete this user.'));
        }

        $user->delete();
        return redirect()->route('users.index')->with('status', __('User deleted successfully.'));
    }

    private function assignableRoles(): array
    {
        return auth()->user()->isSuperAdmin()
            ? User::rolesAssignableBySuperAdmin()
            : User::rolesAssignableByAdmin();
    }

    private function canManageUser(User $user): bool
    {
        $current = auth()->user();
        if ($user->role === 'super_admin' && $current->role !== 'super_admin') {
            return false;
        }
        if ($user->role === 'admin' && $current->role === 'admin' && $user->id !== $current->id) {
            return false; // admin cannot edit other admins
        }
        return true;
    }
}
