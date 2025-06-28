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
        $tableName = config('catalog-manager.tables.category_product', 'category_product');
        $categoriesTable = config('catalog-manager.tables.categories', 'categories');
        $productsTable = config('catalog-manager.tables.products', 'products');

        Schema::create($tableName, function (Blueprint $table) use ($categoriesTable, $productsTable) {
            $table->id(); // Or use composite primary if preferred: $table->primary(['category_id', 'product_id']);

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')
                  ->references('id')
                  ->on($categoriesTable)
                  ->onDelete('cascade');

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on($productsTable)
                  ->onDelete('cascade');

            // To prevent duplicate entries if not using composite primary:
            $table->unique(['category_id', 'product_id']);

            // Timestamps could be added if you need to know when a product was added to a category
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('catalog-manager.tables.category_product', 'category_product');
        Schema::dropIfExists($tableName);
    }
};
