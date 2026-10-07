<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $rolePermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';
        $ownerOnlyPermissions = [
            'audit-logs.view',
            'roles.assign',
            'roles.create',
            'roles.delete',
            'roles.update',
            'roles.view',
            'security.manage',
            'users.create',
            'users.delete',
            'users.suspend',
            'users.update',
            'users.view',
        ];

        DB::table($rolePermissionsTable)
            ->whereIn('role_id', DB::table($rolesTable)->select('id')->where('guard_name', 'web'))
            ->whereIn('permission_id', DB::table($permissionsTable)
                ->select('id')
                ->where('guard_name', 'web')
                ->whereIn('name', $ownerOnlyPermissions))
            ->delete();

        DB::table($modelPermissionsTable)
            ->where('model_type', 'App\\Models\\User')
            ->whereNotIn('model_id', DB::table('users')->where('is_system_owner', true)->select('id'))
            ->whereIn('permission_id', DB::table($permissionsTable)
                ->select('id')
                ->where('guard_name', 'web')
                ->whereIn('name', $ownerOnlyPermissions))
            ->delete();

        $systemOwnerRoleIds = DB::table($rolesTable)
            ->where('name', 'System Owner')
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table($modelRolesTable)
            ->whereIn('role_id', $systemOwnerRoleIds)
            ->where('model_type', 'App\\Models\\User')
            ->whereNotIn('model_id', DB::table('users')->where('is_system_owner', true)->select('id'))
            ->delete();

        $administratorRoleIds = DB::table($rolesTable)
            ->where('name', 'Administrator')
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table($modelRolesTable)
            ->whereIn('role_id', $administratorRoleIds)
            ->where('model_type', 'App\\Models\\User')
            ->whereNotIn('model_id', DB::table('users')->where('is_system_owner', true)->select('id'))
            ->delete();

        DB::table($rolesTable)->whereIn('id', $administratorRoleIds)->update([
            'is_system' => true,
            'is_active' => false,
            'updated_at' => now(),
        ]);

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        throw new LogicException('Restore a database backup to reverse the owner-only permission changes.');
    }
};
