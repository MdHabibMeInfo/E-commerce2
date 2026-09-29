<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request, SecurityEventService $events): RedirectResponse
    {
        $this->authorizeOwner($request);

        $name = $request->input('name');

        if (is_string($name)) {
            $request->merge(['name' => trim($name)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);
        $events->record($request, $request->user(), 'role_created', [
            'role_id' => $role->getKey(),
            'role_name' => $role->name,
            'permission_ids' => $validated['permissions'] ?? [],
        ]);
        $this->notifyOwner($request, 'Management role created', 'A management role was created with permissions.');

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function updatePermissions(Request $request, Role $role, SecurityEventService $events): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureEditableRole($role);

        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);

        $previousPermissionIds = $role->permissions()->pluck('permissions.id')->all();
        $role->syncPermissions($validated['permissions'] ?? []);
        $permissionIds = $role->permissions()->pluck('permissions.id')->all();
        $events->record($request, $request->user(), 'role_permissions_updated', [
            'role_id' => $role->getKey(),
            'role_name' => $role->name,
            'added_permission_ids' => array_values(array_diff($permissionIds, $previousPermissionIds)),
            'removed_permission_ids' => array_values(array_diff($previousPermissionIds, $permissionIds)),
        ]);
        $this->notifyOwner($request, 'Management permissions changed', 'Permissions assigned to a management role were changed.');

        return redirect()->route('admin.roles.index')->with('success', 'Role permissions updated.');
    }

    public function update(Request $request, Role $role, SecurityEventService $events): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureEditableRole($role);

        $name = $request->input('name');

        if (is_string($name)) {
            $request->merge(['name' => trim($name)]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->getKey()),
            ],
        ]);

        $oldName = $role->name;
        $role->name = $validated['name'];
        $role->save();
        $events->record($request, $request->user(), 'role_updated', [
            'role_id' => $role->getKey(),
            'old_name' => $oldName,
            'new_name' => $role->name,
        ]);
        $this->notifyOwner($request, 'Management role renamed', 'A management role name was changed.');

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function updateStatus(Request $request, Role $role, SecurityEventService $events): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureEditableRole($role);
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $role->is_active = $validated['is_active'];
        $role->save();
        $events->record($request, $request->user(), $role->is_active ? 'role_activated' : 'role_deactivated', [
            'role_id' => $role->getKey(),
            'role_name' => $role->name,
        ]);
        $this->notifyOwner($request, 'Management role status changed', 'A management role was activated or deactivated.');

        return redirect()->route('admin.roles.index')->with('success', 'Role status updated.');
    }

    public function destroy(Request $request, Role $role, SecurityEventService $events): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureEditableRole($role);
        abort_if($role->users()->exists(), 422, 'This role is still assigned to one or more users.');

        $events->record($request, $request->user(), 'role_deleted', [
            'role_id' => $role->getKey(),
            'role_name' => $role->name,
        ]);
        $role->delete();
        $this->notifyOwner($request, 'Management role deleted', 'A management role was deleted.');

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->is_system_owner, 403);
    }

    private function ensureEditableRole(Role $role): void
    {
        abort_unless($role->guard_name === 'web' && ! $role->is_system, 404);
    }

    private function notifyOwner(Request $request, string $subject, string $message): void
    {
        $request->user()->notify(new SecurityAlertNotification($subject, $message));
    }
}
