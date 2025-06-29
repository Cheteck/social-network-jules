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
        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('The user who is mentioned'); // Foreign key to users table
            $table->foreignId('mentioner_id')->nullable()->comment('The user who made the mention'); // Foreign key to users table

            $table->morphs('mentionable'); // mentionable_id, mentionable_type (e.g., Post, Comment)

            $table->timestamps();

            // Define foreign key constraints (assuming users table exists)
            // Adjust 'users' table name if different in your main application
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mentioner_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'mentionable_type', 'mentionable_id'], 'user_mentionable_index');
            $table->unique(['user_id', 'mentioner_id', 'mentionable_id', 'mentionable_type'], 'unique_mention_constraint');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mentions');
    }
};
