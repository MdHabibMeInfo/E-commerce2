<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $staffUsers = User::query()
            ->where('account_type', 'admin')
            ->where('is_system_owner', false)
            ->with('roles')
            ->orderBy('name')
            ->get();
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('staffUsers', 'roles'));
    }

    public function store(Request $request, SecurityEventService $events): RedirectResponse
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')->where('is_active', true)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->forceFill([
            'account_type' => 'admin',
            'status' => 'active',
        ])->save();
        $user->syncRoles($validated['roles']);
        $user->sendEmailVerificationNotification();
        $events->record($request, $request->user(), 'management_user_created', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'role_ids' => $validated['roles'],
        ]);
        $request->user()?->notify(new SecurityAlertNotification(
            'Management account created',
            'A management account was created and an email verification link was sent to the new user.',
        ));

        return redirect()->route('admin.users.index')->with('success', 'Staff account created.');
    }

    public function updateRoles(Request $request, User $user, SecurityEventService $events): RedirectResponse
    {
        $this->ensureManageableStaff($request, $user);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')->where('is_active', true)],
        ]);

        $previousRoleIds = $user->roles()->pluck('roles.id')->all();
        $user->syncRoles($validated['roles']);
        $events->record($request, $request->user(), 'management_user_roles_updated', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'added_role_ids' => array_values(array_diff($validated['roles'], $previousRoleIds)),
            'removed_role_ids' => array_values(array_diff($previousRoleIds, $validated['roles'])),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Staff roles updated.');
    }

    public function updateStatus(Request $request, User $user, SecurityEventService $events): RedirectResponse
    {
        $this->ensureManageableStaff($request, $user);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $previousStatus = $user->status;
        $user->forceFill(['status' => $validated['status']])->save();

        if ($validated['status'] === 'suspended') {
            $user->tokens()->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
            }
        }

        $events->record($request, $request->user(), 'management_user_status_updated', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'previous_status' => $previousStatus,
            'new_status' => $validated['status'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Staff account status updated.');
    }

    private function ensureManageableStaff(Request $request, User $user): void
    {
        abort_unless($user->account_type === 'admin' && ! $user->is_system_owner, 404);
        abort_if($request->user()?->is($user), 403);
    }
}
