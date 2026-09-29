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
            ->where('is_system', false)
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')->where('is_active', true)->where('is_system', 0)],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->forceFill([
            'account_type' => 'admin',
            'status' => 'active',
            'password_change_required' => true,
        ])->save();
        $user->syncRoles($validated['roles']);
        $user->sendEmailVerificationNotification();
        $request->user()->notify(new SecurityAlertNotification(
            'Management account created',
            'A new management account was created and assigned roles. The new user must verify their email before access is granted.',
        ));
        $events->record($request, $request->user(), 'staff_account_created', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'role_ids' => $validated['roles'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Staff account created.');
    }

    public function updateRoles(Request $request, User $user, SecurityEventService $events): RedirectResponse
    {
        $this->ensureManageableStaff($request, $user);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')->where('is_active', true)->where('is_system', 0)],
        ]);

        $user->syncRoles($validated['roles']);
        $events->record($request, $request->user(), 'staff_roles_updated', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'role_ids' => $validated['roles'],
        ]);
        $request->user()->notify(new SecurityAlertNotification(
            'Management user roles changed',
            'Roles assigned to a management account were changed by the system owner.',
        ));

        return redirect()->route('admin.users.index')->with('success', 'Staff roles updated.');
    }

    public function updateStatus(Request $request, User $user, SecurityEventService $events): RedirectResponse
    {
        $this->ensureManageableStaff($request, $user);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $user->forceFill(['status' => $validated['status']])->save();

        if ($validated['status'] === 'suspended') {
            DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
            $user->tokens()->delete();
        }

        $events->record($request, $request->user(), 'staff_status_updated', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'status' => $validated['status'],
        ]);
        $request->user()->notify(new SecurityAlertNotification(
            $validated['status'] === 'suspended' ? 'Management account suspended' : 'Management account reactivated',
            'The status of a management account was changed by the system owner.',
        ));

        return redirect()->route('admin.users.index')->with('success', 'Staff account status updated.');
    }

    public function resetTwoFactor(Request $request, User $user, SecurityEventService $events): RedirectResponse
    {
        abort_unless($request->user()?->is_system_owner, 403);
        $this->ensureManageableStaff($request, $user);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_at' => null,
            'remember_token' => SupportStr::random(60),
        ])->save();

        DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
        $user->tokens()->delete();
        $user->notify(new SecurityAlertNotification(
            'Authenticator reset by system owner',
            'Your management authenticator was reset by the system owner. You must enroll a new authenticator before using management tools.',
        ));
        $events->record($request, $request->user(), 'two_factor_reset_by_owner', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Authenticator reset. Staff must enroll again.');
    }

    private function ensureManageableStaff(Request $request, User $user): void
    {
        $this->authorizeOwner($request);
        abort_unless($user->account_type === 'admin' && ! $user->is_system_owner, 404);
        abort_if($request->user()?->is($user), 403);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->is_system_owner, 403);
    }
}
