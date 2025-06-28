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
        $tableName = config('shop-manager.shops_table', 'shops');
        $userModelTable = app(config('shop-manager.user_model'))->getTable(); // Get users table name

        Schema::create($tableName, function (Blueprint $table) use ($userModelTable) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on($userModelTable)->onDelete('cascade');

            $table->boolean('is_active')->default(true);
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->text('address_line_1')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->nullable(); // ISO 3166-1 alpha-2

            $table->json('settings')->nullable(); // For extra shop-specific settings

            // Timestamps for created_at and updated_at
            $table->timestamps();
            // Soft deletes if you want to allow shops to be "deleted" but recoverable
            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('shop-manager.shops_table', 'shops');
        Schema::dropIfExists($tableName);
    }
};
