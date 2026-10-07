<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->where('is_system_owner', true)->count() > 1) {
            throw new RuntimeException('Multiple system owners exist. Resolve them before applying this migration.');
        }

        Schema::table('users', static function (Blueprint $table): void {
            $table->unsignedTinyInteger('system_owner_slot')->nullable()->unique();
        });

        DB::table('users')
            ->where('is_system_owner', true)
            ->update(['system_owner_slot' => 1]);
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropUnique(['system_owner_slot']);
            $table->dropColumn('system_owner_slot');
        });
    }
};
