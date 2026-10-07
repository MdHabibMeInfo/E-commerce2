<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->string('code', 40)->nullable()->change();
            $table->boolean('is_automatic')->default(false)->after('code')->index();
            $table->string('target_type', 20)->default('all')->after('is_automatic');
            $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            $table->index(['target_type', 'target_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('delivery_discount', 10, 2)->default(0)->after('delivery_fee');
        });

        Schema::table('coupon_redemptions', function (Blueprint $table): void {
            $table->decimal('delivery_discount', 10, 2)->default(0)->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table): void {
            $table->dropColumn('delivery_discount');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_discount');
        });

        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropIndex(['target_type', 'target_id']);
            $table->dropIndex(['is_automatic']);
            $table->dropColumn(['is_automatic', 'target_type', 'target_id']);
        });
    }
};
