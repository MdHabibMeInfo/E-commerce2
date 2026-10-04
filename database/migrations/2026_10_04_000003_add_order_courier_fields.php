<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending');
            $table->string('shipping_status')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('courier_tracking_code')->nullable();
            $table->timestamp('shipment_created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'shipping_status',
                'courier_name',
                'courier_tracking_code',
                'shipment_created_at',
            ]);
        });
    }
};
