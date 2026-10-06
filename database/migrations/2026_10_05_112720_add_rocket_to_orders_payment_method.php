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
        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cod', 'online', 'bkash', 'nagad', 'rocket'])->default('cod')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')
            ->where('payment_method', 'rocket')
            ->update(['payment_method' => 'online']);

        Schema::table('orders', function (Blueprint $table): void {
            $table->enum('payment_method', ['cod', 'online', 'bkash', 'nagad'])->default('cod')->change();
        });
    }
};
