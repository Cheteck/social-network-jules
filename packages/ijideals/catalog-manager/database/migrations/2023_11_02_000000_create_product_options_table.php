<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up()
        {
            $tableName = config('catalog-manager.tables.product_options', 'product_options');

            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., "Color", "Size", "Material"
                $table->string('display_type')->default('dropdown'); // e.g., dropdown, radio, color_swatch, text_input
                // $table->unsignedBigInteger('shop_id')->nullable(); // If options can be shop-specific
                // $table->foreign('shop_id')->references('id')->on(config('catalog-manager.shop_model_table','shops'))->onDelete('cascade');
                $table->timestamps();

                // Option name should be unique (globally for MVP)
                $table->unique('name');
            });
        }

        public function down()
        {
            $tableName = config('catalog-manager.tables.product_options', 'product_options');
            Schema::dropIfExists($tableName);
        }
    };
