<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up()
        {
            $tableName = config('catalog-manager.tables.product_option_values', 'product_option_values');
            $optionsTable = config('catalog-manager.tables.product_options', 'product_options');

            Schema::create($tableName, function (Blueprint $table) use ($optionsTable) {
                $table->id();
                $table->unsignedBigInteger('product_option_id');
                $table->foreign('product_option_id')
                      ->references('id')
                      ->on($optionsTable)
                      ->onDelete('cascade');

                $table->string('value'); // e.g., "S", "M", "Red", "Cotton"
                $table->string('display_label')->nullable(); // e.g., "Small", "Medium", "Crimson Red"
                $table->integer('order_column')->default(0); // For sorting values within an option
                $table->timestamps();

                // A value should be unique for a given option type
                $table->unique(['product_option_id', 'value']);
            });
        }

        public function down()
        {
            $tableName = config('catalog-manager.tables.product_option_values', 'product_option_values');
            Schema::dropIfExists($tableName);
        }
    };
