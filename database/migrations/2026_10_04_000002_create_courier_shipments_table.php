<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->string('courier_code');
            $table->string('external_shipment_id')->nullable();
            $table->string('consignment_id')->nullable();
            $table->string('tracking_code')->nullable();
            $table->string('status')->default('created');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'courier_code']);
            $table->index('tracking_code');
            $table->index(['courier_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_shipments');
    }
};
