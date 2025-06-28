<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up()
        {
            $tableName = config('catalog-manager.tables.product_product_option_pivot', 'product_product_option');
            $productsTable = config('catalog-manager.tables.products', 'products');
            $optionsTable = config('catalog-manager.tables.product_options', 'product_options');

            Schema::create($tableName, function (Blueprint $table) use ($productsTable, $optionsTable) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->foreign('product_id')
                      ->references('id')
                      ->on($productsTable)
                      ->onDelete('cascade');

                $table->unsignedBigInteger('product_option_id');
                $table->foreign('product_option_id')
                      ->references('id')
                      ->on($optionsTable)
                      ->onDelete('cascade');

                // Optional: store product-specific allowed values for this option as JSON
                // $table->json('allowed_option_value_ids')->nullable();
                // Or, this pivot could directly link product to product_option_values if an option's values
                // are not global but defined per-product for that option type.
                // For MVP, we assume product uses global options, and variants pick from global values.

                $table->timestamps();
                $table->unique(['product_id', 'product_option_id']);
            });
        }

        public function down()
        {
            $tableName = config('catalog-manager.tables.product_product_option_pivot', 'product_product_option');
            Schema::dropIfExists($tableName);
        }
    };
