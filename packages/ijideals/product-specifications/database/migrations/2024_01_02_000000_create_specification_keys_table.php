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
        Schema::create('specification_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('e.g., Material, Weight, Dimensions, Color');
            $table->string('type')->default('string')->comment('e.g., string, number, boolean, select - hints for input type');
            $table->string('unit')->nullable()->comment('e.g., cm, kg, g, inches, lbs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('specification_keys');
    }
};
