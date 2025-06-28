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
        Schema::create('user_profiles', function (Blueprint $table) {
            // Assuming User model primary key is unsignedBigInteger.
            // Get this from config or User model directly if possible for more robustness.
            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            $userTable = (new $userModel)->getTable(); // users
            $userKeyName = (new $userModel)->getKeyName(); // id

            $table->foreignId('user_id')->constrained($userTable, $userKeyName)->onDelete('cascade');
            $table->primary('user_id'); // A user has one profile

            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->date('birth_date')->nullable();
            // Add fields for avatar_path and cover_photo_path later if handling uploads here
            // $table->string('avatar_path')->nullable();
            // $table->string('cover_photo_path')->nullable();
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
        Schema::dropIfExists('user_profiles');
    }
};
