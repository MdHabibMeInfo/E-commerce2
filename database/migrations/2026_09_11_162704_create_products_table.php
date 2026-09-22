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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->string('slug')
                ->unique();

            $table->string('sku')
                ->nullable()
                ->unique();

            $table->string('short_description')
                ->nullable();

            $table->longText('description')
                ->nullable();

            $table->decimal('price', 12, 2)
                ->nullable();

            $table->decimal('compare_at_price', 12, 2)
                ->nullable();

            $table->unsignedInteger('stock')
                ->nullable();

            $table->string('type')
                ->default('simple')
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
