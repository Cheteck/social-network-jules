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
        $tableName = config('scout.database.table', 'scout_index');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('index_name'); // Name of the Scout index (e.g., 'users', 'posts')
            $table->unsignedBigInteger('document_id'); // The ID of the model being indexed

            // Store all indexed terms in a single column for simple LIKE matching.
            // Using TEXT for potentially long strings of concatenated model attributes.
            $table->text('content');

            // Optional: Store the model type for potential multi-model index table (though Scout usually uses separate tables or prefixes)
            // $table->string('model_type')->nullable();

            $table->timestamps(); // created_at for when the index record was created/updated

            $table->index(['index_name', 'document_id']);
            // Add a fulltext index on 'content' for better search performance if your DB supports it well.
            // For MySQL: $table->fullText('content');
            // For PostgreSQL: You'd use a GIN or GIST index, often on a tsvector column.
            // SQLite does not support FULLTEXT indexes in the same way without FTS extensions.
            // For simplicity with the 'database' driver, LIKE queries are often used by default implementations or simple custom ones.
        });

        // If using MySQL and wanting basic full-text search (less sophisticated than dedicated engines)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE {$tableName} ADD FULLTEXT fulltext_content_idx (content)");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('scout.database.table', 'scout_index');
        Schema::dropIfExists($tableName);
    }
};
