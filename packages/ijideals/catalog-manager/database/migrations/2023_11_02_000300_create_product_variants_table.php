<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up()
        {
            $tableName = config('catalog-manager.tables.product_variants', 'product_variants');
            $productsTable = config('catalog-manager.tables.products', 'products');
            $shopsTable = app(config('catalog-manager.shop_model'))->getTable(); // To get shop_id for unique SKU

            Schema::create($tableName, function (Blueprint $table) use ($productsTable, $shopsTable) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->foreign('product_id')
                      ->references('id')
                      ->on($productsTable)
                      ->onDelete('cascade');

                $table->string('sku')->nullable(); // Stock Keeping Unit for the variant

                // Price:
                // Option 1: Store the absolute price for the variant
                $table->decimal('price', 10, 2)->nullable();
                // Option 2: Store a price modifier relative to the base product's price
                // $table->decimal('price_modifier', 10, 2)->nullable()->default(0.00);
                // The model currently uses price_modifier, let's stick to that for now or adjust model.
                // For flexibility, let's allow both, with 'price' taking precedence.
                $table->decimal('price_modifier', 10, 2)->nullable();


                $table->integer('stock_quantity')->nullable()->default(0);
                $table->boolean('manage_stock')->default(config('catalog-manager.stock_management_enabled', true));

                $table->boolean('is_active')->default(true);
                $table->json('properties')->nullable(); // For variant-specific properties not covered by options

                $table->timestamps();

                // SKU should be unique per shop (indirectly via product->shop_id)
                // or globally unique depending on requirements. For now, let's make it unique per product.
                // A true unique SKU per shop would require joining with products table to get shop_id.
                // Let's make it unique per product for simplicity now, can be adjusted.
                $table->unique(['product_id', 'sku']);
                // If SKU must be globally unique (or unique per shop), the application logic must enforce it,
                // or a more complex unique index involving a trigger or a generated column would be needed if
                // shop_id is not directly on this table.
                // For now, assuming SKU is unique per product is a good start.
            });
        }

        public function down()
        {
            $tableName = config('catalog-manager.tables.product_variants', 'product_variants');
            Schema::dropIfExists($tableName);
        }
    };
