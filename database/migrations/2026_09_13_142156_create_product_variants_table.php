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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')
                ->nullable();

            $table->string('sku')
                ->unique();

            $table->string('combination_hash', 64);

            $table->decimal('price', 12, 2);

            $table->decimal('compare_at_price', 12, 2)
                ->nullable();

            $table->unsignedInteger('stock')
                ->default(0);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->unique(
                ['product_id', 'combination_hash'],
                'product_variant_combination_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
