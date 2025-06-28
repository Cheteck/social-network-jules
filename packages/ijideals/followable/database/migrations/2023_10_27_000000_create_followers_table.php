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
        Schema::create('followers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id'); // The user who is following
            $table->morphs('followable'); // The model being followed (e.g., User, Post, etc.)
            $table->timestamps();

            $table->primary(['user_id', 'followable_id', 'followable_type'], 'followers_primary');

            // Assuming your users table is named 'users' and its primary key is 'id'
            // Adjust if your User model/table is different.
            // We get the user table name from the auth configuration to be more flexible.
            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            $userTable = (new $userModel)->getTable();
            $userKeyName = (new $userModel)->getKeyName();

            $table->foreign('user_id')
                  ->references($userKeyName)
                  ->on($userTable)
                  ->onDelete('cascade');

            // It's good practice to add an index for lookups,
            // especially if you query by followable_id and followable_type often.
            $table->index(['followable_id', 'followable_type'], 'followable_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('followers');
    }
};
