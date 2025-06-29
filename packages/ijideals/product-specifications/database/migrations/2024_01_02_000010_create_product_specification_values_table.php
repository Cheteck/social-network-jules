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
        Schema::create('product_specification_values', function (Blueprint $table) {
            $table->id();

            // Assuming 'products' table is from ijideals/catalog-manager
            // and its primary key is 'id' of type bigInteger.
            // Adjust if the products table has a different name or primary key type.
            $table->foreignId('product_id');
            $table->foreignId('specification_key_id');

            $table->text('value'); // Using text to accommodate various value types simply.
                                   // Could be json or multiple type-specific columns (e.g., string_value, numeric_value)
                                   // for more structured data, but text is simpler to start.
            $table->timestamps();

            // Foreign key constraints
            // Note: Constraint for 'products' table might fail if that table doesn't exist when this migration runs.
            // This often requires careful ordering of migrations or specific handling in package tests.
            // For now, we assume 'products' table will exist.
            // The actual table name for products from catalog-manager package needs to be confirmed.
            // Let's assume it's 'products' as a common convention.
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products') // This table name MUST match the one used by ijideals/catalog-manager
                  ->onDelete('cascade');

            $table->foreign('specification_key_id')
                  ->references('id')
                  ->on('specification_keys')
                  ->onDelete('cascade');

            $table->unique(['product_id', 'specification_key_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_specification_values');
    }
};
