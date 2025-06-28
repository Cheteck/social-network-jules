<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up()
        {
            $tableName = config('catalog-manager.tables.product_variant_option_value_pivot', 'product_variant_option_values');
            $variantsTable = config('catalog-manager.tables.product_variants', 'product_variants');
            $optionValuesTable = config('catalog-manager.tables.product_option_values', 'product_option_values');

            Schema::create($tableName, function (Blueprint $table) use ($variantsTable, $optionValuesTable) {
                $table->id(); // Simple auto-incrementing ID for the pivot record

                $table->unsignedBigInteger('product_variant_id');
                $table->foreign('product_variant_id', 'pvov_variant_foreign') // Naming the foreign key constraint
                      ->references('id')
                      ->on($variantsTable)
                      ->onDelete('cascade');

                $table->unsignedBigInteger('product_option_value_id');
                $table->foreign('product_option_value_id', 'pvov_option_value_foreign') // Naming the foreign key constraint
                      ->references('id')
                      ->on($optionValuesTable)
                      ->onDelete('cascade');

                // A variant should not have the same option value twice.
                // Also, a variant is defined by a unique SET of option values.
                // e.g., Variant A = (Color: Red, Size: S), Variant B = (Color: Blue, Size: S)
                // The combination of (product_variant_id, product_option_value_id) must be unique.
                $table->unique(['product_variant_id', 'product_option_value_id'], 'pvov_variant_option_value_unique');

                // No timestamps needed for this pivot table typically
            });
        }

        public function down()
        {
            $tableName = config('catalog-manager.tables.product_variant_option_value_pivot', 'product_variant_option_values');
            Schema::dropIfExists($tableName);
        }
    };
