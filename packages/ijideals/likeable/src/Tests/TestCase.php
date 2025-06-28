<?php

namespace Ijideals\Likeable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ijideals\Likeable\Providers\LikeableServiceProvider;
use Ijideals\Likeable\Traits\CanLike;
use Ijideals\Likeable\Traits\CanBeLiked;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Ijideals\Likeable\Http\Controllers\LikeController;

class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        $this->setupRoutes();
    }

    protected function getPackageProviders($app)
    {
        return [
            LikeableServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('likeable.user_model', User::class);
        $app['config']->set('likeable.like_model', \Ijideals\Likeable\Models\Like::class);
        $app['config']->set('likeable.table_name', 'likes');

        // For API authentication in tests
        $app['config']->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUpDatabase($app)
    {
        // Create users table for testing
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('api_token', 80)->unique()->nullable()->default(null); // For API auth
            $table->timestamps();
        });

        // Create posts table for testing (as a likeable entity)
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Create articles table for testing (another likeable entity without user_id)
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Run the package's migrations
        $migration = include __DIR__.'/../../database/migrations/2023_01_01_000000_create_likes_table.php';
        $migration->up();
    }

    protected function setupRoutes()
    {
        // Define API routes for testing
        Route::middleware('auth:api')
            ->prefix('api/v1')
            ->group(function () {
                Route::post('/{likeable_type}/{likeable_id}/like', [LikeController::class, 'store'])->name('likeable.like.test');
                Route::delete('/{likeable_type}/{likeable_id}/unlike', [LikeController::class, 'destroy'])->name('likeable.unlike.test');
            });

        // Define a morph map for tests
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'post' => Post::class,
            'article' => Article::class,
        ]);
    }
}

// Test User Model
class User extends Authenticatable
{
    use CanLike; // Trait for users who can like items
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'api_token'];
    public $timestamps = false;
}

// Test Post Model (Likeable)
class Post extends Model
{
    use CanBeLiked; // Trait for items that can be liked
    protected $table = 'posts';
    protected $fillable = ['title', 'user_id'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Test Article Model (Another Likeable, no direct user relation)
class Article extends Model
{
    use CanBeLiked;
    protected $table = 'articles';
    protected $fillable = ['name'];
    public $timestamps = false;
}
