<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = config('permission.table_names');
        $rolesTable = $tables['roles'] ?? 'roles';
        $modelRolesTable = $tables['model_has_roles'] ?? 'model_has_roles';
        $systemOwnerRoleId = DB::table($rolesTable)
            ->where('name', 'System Owner')
            ->where('guard_name', 'web')
            ->where('is_system', true)
            ->value('id');

        if ($systemOwnerRoleId === null) {
            throw new LogicException('The reserved System Owner role must exist before owner accounts can be repaired.');
        }

        DB::table($rolesTable)->where('id', $systemOwnerRoleId)->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        foreach (DB::table('users')->where('is_system_owner', true)->pluck('id') as $ownerId) {
            DB::table($modelRolesTable)->insertOrIgnore([
                'role_id' => $systemOwnerRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $ownerId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('System Owner role repair is intentionally forward-only; restore a database backup to reverse it.');
    }
};
