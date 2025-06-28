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
        $tableName = config('user-settings.table_name', 'user_settings');
        // Get user table name from global auth config for the foreign key
        $userModel = app(config('auth.providers.users.model', \App\Models\User::class));
        $usersTable = $userModel->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($usersTable, $userModel) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references($userModel->getKeyName()) // Use configured primary key name of User model
                  ->on($usersTable)
                  ->onDelete('cascade');

            $table->string('key'); // e.g., 'notifications.new_like.email', 'privacy.profile_visibility'
            $table->text('value')->nullable(); // Store as text; can be JSON, string, number, boolean ('1'/'0')

            $table->timestamps();

            $table->unique(['user_id', 'key']); // Each user can only have one entry per setting key
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('user-settings.table_name', 'user_settings');
        Schema::dropIfExists($tableName);
    }
};
