<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('catalog-manager.tables.products', 'products');
        $shopsTable = app(config('catalog-manager.shop_model'))->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($shopsTable) {
            $table->id();

            $table->unsignedBigInteger('shop_id');
            $table->foreign('shop_id')->references('id')->on($shopsTable)->onDelete('cascade');

            $table->string('name');
            $table->string('slug'); // Should be unique per shop
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('sku')->nullable(); // Stock Keeping Unit

            $table->integer('stock_quantity')->nullable()->default(0);
            $table->boolean('manage_stock')->default(config('catalog-manager.stock_management_enabled', true)); // Whether stock is managed

            $table->boolean('is_active')->default(true); // Is the product visible/purchasable
            $table->boolean('is_featured')->default(false); // For highlighting products

            $table->json('properties')->nullable(); // For custom attributes like size, color, material etc.

            $table->timestamps();
            // $table->softDeletes();

            $table->unique(['shop_id', 'slug']);
            $table->index('sku');
            $table->index('is_active');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('catalog-manager.tables.products', 'products');
        Schema::dropIfExists($tableName);
    }
};
