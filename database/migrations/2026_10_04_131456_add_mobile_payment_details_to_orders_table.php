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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cod', 'online', 'bkash', 'nagad'])->default('cod')->change();
            $table->string('payment_sender_phone', 20)->nullable();
            $table->string('payment_transaction_id', 100)->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('payment_proof_path', 2048)->nullable();
            $table->foreignId('payment_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_reviewed_at')->nullable();
            $table->unique(['payment_method', 'payment_transaction_id'], 'orders_payment_method_transaction_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')
            ->whereIn('payment_method', ['bkash', 'nagad'])
            ->update(['payment_method' => 'online']);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_payment_method_transaction_unique');
            $table->dropForeign(['payment_reviewed_by']);
            $table->enum('payment_method', ['cod', 'online'])->default('cod')->change();
            $table->dropColumn([
                'payment_sender_phone',
                'payment_transaction_id',
                'payment_amount',
                'payment_proof_path',
                'payment_reviewed_by',
                'payment_reviewed_at',
            ]);
        });
    }
};
