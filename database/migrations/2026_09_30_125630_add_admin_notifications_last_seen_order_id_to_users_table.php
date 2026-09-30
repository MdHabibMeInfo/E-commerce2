<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_notifications_last_seen_order_id')->nullable();
        });

        $latestOrderId = (int) (DB::table('orders')->max('id') ?? 0);

        DB::table('users')->update([
            'admin_notifications_last_seen_order_id' => $latestOrderId,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_notifications_last_seen_order_id');
        });
    }
};
