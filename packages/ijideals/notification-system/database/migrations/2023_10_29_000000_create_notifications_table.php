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
        $tableName = config('notification-system.table_name', 'notifications');
        // Note: We don't fetch user model details here as user_id is just a foreign key
        // to the users table defined in the main application.

        Schema::create($tableName, function (Blueprint $table) {
            $table->uuid('id')->primary(); // Using UUID for notification IDs is common

            $table->unsignedBigInteger('user_id'); // The recipient of the notification
            $table->foreign('user_id')
                  ->references('id') // Assumes 'id' is the primary key of your users table
                  ->on(app(config('notification-system.user_model'))->getTable()) // Get users table name dynamically
                  ->onDelete('cascade');

            $table->string('type'); // Type of notification (e.g., 'new_like', 'new_comment')
            $table->text('data');   // JSON column to store notification-specific data
                                    // (e.g., post_id, comment_id, actor_id)

            $table->timestamp('read_at')->nullable();
            $table->timestamps(); // created_at, updated_at

            $table->index('user_id');
            $table->index(['user_id', 'read_at']); // Useful for fetching unread notifications for a user
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('notification-system.table_name', 'notifications');
        Schema::dropIfExists($tableName);
    }
};
