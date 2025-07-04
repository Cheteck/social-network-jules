<?php

namespace Ijideals\Likeable\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User; // Main app's User model
use Ijideals\Likeable\Providers\LikeableServiceProvider;
use Ijideals\Likeable\Concerns\CanLike; // Trait for User model
use Ijideals\SocialPosts\Models\Post; // Example Likeable model
use Ijideals\SocialPosts\Concerns\CanBeLiked as CanBeLikedPostTrait; // Example Likeable trait for Post
use Ijideals\Likeable\Contracts\LikeableContract;


abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            LikeableServiceProvider::class,
            SocialPostsServiceProvider::class, // For Post model & factory
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('sanctum.middleware.encrypt_cookies', false); // Useful for API tests
    }

    protected function setUpDatabase($app)
    {
        // Load default Laravel migrations (users table)
        $this->loadLaravelMigrations(['--database' => 'testbench']);

        // Load migrations from dependent packages
        $this->loadMigrationsFrom(__DIR__ . '/../../social-posts/database/migrations');

        // Load this package's migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Run all migrations
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        // Manually create tables for simple test models if they don't have migrations
        if (!Schema::hasTable('test_posts')) { // Used by LikeableTest
            Schema::create('test_posts', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('test_articles')) { // Used by CanBeLikedTest
             Schema::create('test_articles', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createPost(array $attributes = []): Post
    {
        // Ensure author_id and author_type are set if not provided,
        // as Post model from social-posts expects them.
        if (!isset($attributes['author_id']) || !isset($attributes['author_type'])) {
            $user = $this->createUser();
            $attributes['author_id'] = $user->id;
            $attributes['author_type'] = get_class($user);
        }
        return Post::factory()->create($attributes);
    }
}
