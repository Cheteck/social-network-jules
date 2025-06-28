<?php

namespace Ijideals\HashtagSystem\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ijideals\HashtagSystem\Traits\HasHashtags;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Optional: if you want to use factories for TestPost

class TestPost extends Model
{
    use HasHashtags;
    // use HasFactory; // Uncomment if you create a factory for TestPost

    protected $table = 'test_posts_for_api'; // Use a different table name to avoid collision if trait test runs in same suite without full refresh
    protected $guarded = [];
    public $timestamps = true; // APIs often involve timestamps

    /**
     * A helper method to set up the schema for this model during tests.
     */
    public static function migrate()
    {
        if (!Schema::hasTable((new static)->getTable())) {
            Schema::create((new static)->getTable(), function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content')->nullable();
                $table->timestamps();
            });
        }
    }

    // If you decide to create a factory:
    // protected static function newFactory()
    // {
    //     return \Ijideals\HashtagSystem\Tests\TestSupport\Database\Factories\TestPostFactory::new();
    // }
}
