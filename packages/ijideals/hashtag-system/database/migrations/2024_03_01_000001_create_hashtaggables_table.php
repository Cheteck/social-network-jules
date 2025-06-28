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
        Schema::create('hashtaggables', function (Blueprint $table) {
            $table->unsignedBigInteger('hashtag_id');
            $table->morphs('hashtaggable'); // This will create `hashtaggable_id` and `hashtaggable_type`

            $table->primary(['hashtag_id', 'hashtaggable_id', 'hashtaggable_type'], 'hashtaggables_primary');

            $table->foreign('hashtag_id')
                  ->references('id')
                  ->on('hashtags')
                  ->onDelete('cascade');

            // It's good practice to add an index for faster lookups by type if you query that way often.
            $table->index('hashtaggable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hashtaggables');
    }
};
