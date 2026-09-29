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
        $roleId = DB::table('roles')
            ->where('name', 'System Owner')
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId === null) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'System Owner',
                'guard_name' => 'web',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('roles')->where('id', $roleId)->update([
                'is_system' => true,
                'is_active' => true,
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('users')->where('is_system_owner', true)->pluck('id') as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->where('name', 'System Owner')
            ->where('guard_name', 'web')
            ->where('is_system', true)
            ->pluck('id');

        DB::table('model_has_roles')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();
    }
};
