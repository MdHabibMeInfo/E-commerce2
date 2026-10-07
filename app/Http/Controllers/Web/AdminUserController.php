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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeOwner($request);

        $staffUsers = User::query()
            ->where('account_type', 'admin')
            ->where('is_system_owner', false)
            ->with('roles')
            ->orderBy('name')
            ->get();
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->whereNotIn('name', ['Administrator', 'System Owner'])
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('staffUsers', 'roles'));
    }

    public function store(Request $request, SecurityEventService $events): RedirectResponse
    {
        $this->authorizeOwner($request);

        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        $reservedRoleIds = $this->reservedRoleIds();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'web')->where('is_active', true),
                Rule::notIn($reservedRoleIds),
            ],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::random(64),
        ]);
        $user->forceFill([
            'account_type' => 'admin',
            'status' => 'active',
        ])->save();
        $user->syncRoles($validated['roles']);
        $user->sendPasswordResetNotification(Password::broker()->createToken($user));
        $user->sendEmailVerificationNotification();
        $user->notify(new SecurityAlertNotification(
            'Management account created',
            'A management account was created for you. Use the password setup and email verification links sent to this address, then enroll an authenticator before managing the site.',
        ));
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

        $reservedRoleIds = $this->reservedRoleIds();
        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'web')->where('is_active', true),
                Rule::notIn($reservedRoleIds),
            ],
        ]);

        $previousRoleIds = $user->roles()->pluck('roles.id')->all();
        $user->syncRoles($validated['roles']);
        $addedRoleIds = array_values(array_diff($validated['roles'], $previousRoleIds));
        $removedRoleIds = array_values(array_diff($previousRoleIds, $validated['roles']));

        if ($addedRoleIds !== [] || $removedRoleIds !== []) {
            $this->revokeStaffSessions($user);
            $user->notify(new SecurityAlertNotification(
                'Management roles changed',
                'The roles assigned to your management account were changed. All active sessions were signed out.',
            ));
            $request->user()?->notify(new SecurityAlertNotification(
                'Management account roles changed',
                'Roles assigned to a management account were changed.',
            ));
        }

        $events->record($request, $request->user(), 'management_user_roles_updated', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'added_role_ids' => $addedRoleIds,
            'removed_role_ids' => $removedRoleIds,
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

        if ($previousStatus !== $validated['status']) {
            if ($validated['status'] === 'suspended') {
                $this->revokeStaffSessions($user);
            }

            $user->notify(new SecurityAlertNotification(
                'Management account status changed',
                'Your management account status was changed to '.$validated['status'].'.',
            ));
            $request->user()?->notify(new SecurityAlertNotification(
                'Management account status changed',
                'A management account status was changed to '.$validated['status'].'.',
            ));
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
        $this->authorizeOwner($request);
        abort_unless($user->account_type === 'admin' && ! $user->is_system_owner, 404);
        abort_if($request->user()?->is($user), 403);
    }

    private function authorizeOwner(Request $request): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_system_owner && $user->status === 'active', 403);
    }

    /** @return list<int> */
    private function reservedRoleIds(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['Administrator', 'System Owner'])
            ->pluck('id')
            ->all();
    }

    private function revokeStaffSessions(User $user): void
    {
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
        }
    }
}
