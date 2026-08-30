<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewUserRole;
use App\Models\User;
use App\Support\Interview\InterviewAuditLogger;
use App\Support\PaginationHelper;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    public function index(Request $request): View
    {
        $rolesConfig = config('interview.roles');

        $query = User::query()
            ->with('interviewRoles')
            ->where(function ($q) {
                $q->where('role', 'interview')
                    ->orWhereHas('interviewRoles');
            })
            ->orderBy('name');

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->q, '%_\\').'%';
            $query->where(function ($qry) use ($term) {
                $qry->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        if ($request->filled('interview_role')) {
            $role = $request->interview_role;
            abort_unless(array_key_exists($role, $rolesConfig), 404);
            $query->whereHas('interviewRoles', fn ($q) => $q->where('role', $role));
        }

        if ($request->filled('type')) {
            if ($request->type === 'interview_only') {
                $query->where('role', 'interview');
            } elseif ($request->type === 'shared') {
                $query->where('role', '!=', 'interview');
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereHas('interviewRoles');
            } elseif ($request->status === 'inactive') {
                $query->whereDoesntHave('interviewRoles');
            }
        }

        $users = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();

        // For the "assign existing user" picker — people not yet enrolled
        $assignableUsers = User::orderBy('name')
            ->whereDoesntHave('interviewRoles')
            ->where('role', '!=', 'interview')
            ->get(['id', 'name', 'email', 'role']);

        return view('interview.user-roles.index', [
            'users' => $users,
            'rolesConfig' => $rolesConfig,
            'assignableUsers' => $assignableUsers,
            'filters' => [
                'q' => $request->q,
                'interview_role' => $request->interview_role,
                'type' => $request->type,
                'status' => $request->status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('interview.user-roles.create', [
            'rolesConfig' => config('interview.roles'),
        ]);
    }

    /**
     * Create a new interview-only user and assign interview roles.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $validRoles = array_keys(config('interview.roles'));

        $request->validate([
            'first_name' => ValidationRules::personName(),
            'middle_name' => ValidationRules::personName(false),
            'last_name' => ValidationRules::personName(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ValidationRules::password(),
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:'.implode(',', $validRoles)],
        ], ValidationRules::requiredMessages(), [
            'first_name' => __('first name'),
            'middle_name' => __('middle name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'password' => __('password'),
            'roles' => __('interview roles'),
        ]);

        $name = trim($request->first_name.' '.($request->middle_name ?? '').' '.$request->last_name);

        $user = User::create([
            'name' => $name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'interview', // system role: no training menus
            'email_verified_at' => now(),
            'registration_status' => 'approved',
        ]);

        foreach ($request->roles as $role) {
            InterviewUserRole::create([
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        InterviewAuditLogger::log(
            'interview_user_created',
            'Interview-only user created',
            $request->user(),
            null,
            ['user_id' => $user->id, 'roles' => $request->roles]
        );

        return redirect()
            ->route('interview.user-roles.index')
            ->with('status', __(':name created as interview-only user.', ['name' => $user->name]));
    }

    /**
     * Sync interview roles for an existing system user (staff/admin/etc or interview-only).
     */
    public function store(Request $request): RedirectResponse
    {
        $validRoles = array_keys(config('interview.roles'));

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'in:'.implode(',', $validRoles)],
        ]);

        $userId = (int) $data['user_id'];
        $newRoles = $data['roles'] ?? [];

        $existing = InterviewUserRole::where('user_id', $userId)->pluck('role')->all();
        $toAdd = array_diff($newRoles, $existing);
        $toRemove = array_diff($existing, $newRoles);

        foreach ($toAdd as $role) {
            InterviewUserRole::create(['user_id' => $userId, 'role' => $role]);
        }

        if ($toRemove !== []) {
            InterviewUserRole::where('user_id', $userId)
                ->whereIn('role', $toRemove)
                ->delete();
        }

        InterviewAuditLogger::log(
            'interview_roles_synced',
            'Interview module roles updated for user',
            $request->user(),
            null,
            [
                'user_id' => $userId,
                'added' => array_values($toAdd),
                'removed' => array_values($toRemove),
                'final' => $newRoles,
            ]
        );

        $user = User::find($userId);

        return back()->with('status', __(':name interview roles updated.', ['name' => $user->name]));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validRoles = array_keys(config('interview.roles'));

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'in:'.implode(',', $validRoles)],
        ]);

        $request->merge(['user_id' => $user->id, 'roles' => $data['roles'] ?? []]);

        return $this->store($request);
    }

    public function destroy(Request $request, User $user, string $role): RedirectResponse
    {
        abort_unless(array_key_exists($role, config('interview.roles')), 404);

        InterviewUserRole::where('user_id', $user->id)->where('role', $role)->delete();

        InterviewAuditLogger::log(
            'interview_role_removed',
            'Interview module role removed',
            $request->user(),
            null,
            ['user_id' => $user->id, 'role' => $role]
        );

        return back()->with('status', __('Role removed.'));
    }
}
