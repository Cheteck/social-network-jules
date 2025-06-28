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
        $tableName = config('catalog-manager.tables.categories', 'categories');

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // Slugs should be unique globally for simplicity in MVP
            $table->text('description')->nullable();

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                  ->references('id')
                  ->on($tableName) // Self-referential
                  ->onDelete('cascade'); // Or set null if children should become top-level

            // Optional: if categories were shop-specific
            // $table->unsignedBigInteger('shop_id')->nullable();
            // $table->foreign('shop_id')->references('id')->on(config('catalog-manager.shop_model_table','shops'))->onDelete('cascade');

            $table->timestamps();
            // $table->softDeletes(); // If categories can be soft-deleted
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('catalog-manager.tables.categories', 'categories');
        Schema::dropIfExists($tableName);
    }
};
