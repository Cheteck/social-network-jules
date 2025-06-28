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
        Schema::create('likes', function (Blueprint $table) {
            // Foreign key to the user who liked the item.
            // Get user table and key name from User model for robustness.
            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            $userKeyName = (new $userModel)->getKeyName(); // Typically 'id'
            $userTable = (new $userModel)->getTable();   // Typically 'users'

            $table->foreignId('user_id')->constrained($userTable, $userKeyName)->onDelete('cascade');

            // Polymorphic relationship columns for the likeable item.
            $table->morphs('likeable'); // Creates 'likeable_id' (unsignedBigInteger) and 'likeable_type' (string)

            $table->timestamps(); // Optional: 'created_at' can track when the like was made.

            // Define a composite primary key to ensure a user can only like an item once.
            $table->primary(['user_id', 'likeable_id', 'likeable_type'], 'user_likeable_primary');

            // Additional index for querying likes by likeable item (e.g., to count likes for a post).
            // The morphs() method already creates an index: $table->index(['likeable_id', 'likeable_type']);
            // So, this might be redundant depending on exact needs or if morphs() index is sufficient.
            // If more specific querying on likeable_type is needed, a separate index on just likeable_type could be useful.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('likes');
    }
};
