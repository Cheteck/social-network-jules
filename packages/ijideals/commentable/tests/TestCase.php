<?php

namespace Ijideals\Commentable\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\Commentable\Providers\CommentableServiceProvider;
use Ijideals\SocialPosts\Providers\SocialPostsServiceProvider; // For Post model
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use App\Models\User; // Main app User model
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
            CommentableServiceProvider::class,
            SocialPostsServiceProvider::class, // For Post model & factory
            // Add other necessary service providers
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
        $app['config']->set('sanctum.middleware.encrypt_cookies', false);
    }

    protected function setUpDatabase($app)
    {
        // Load default Laravel migrations (users table)
        $this->loadLaravelMigrations(['--database' => 'testbench']);

        // Load migrations from dependent packages
        $this->loadMigrationsFrom(__DIR__ . '/../../social-posts/database/migrations');

        // Load this package's migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        // Example: Manually create a table for a test-specific model if needed
        // if (!Schema::hasTable('test_commentables')) {
        //     Schema::create('test_commentables', function (Blueprint $table) {
        //         $table->id();
        //         $table->string('name')->nullable();
        //         $table->timestamps();
        //     });
        // }
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createPost(array $attributes = []): \Ijideals\SocialPosts\Models\Post
    {
        if (!isset($attributes['author_id']) || !isset($attributes['author_type'])) {
            $user = $this->createUser();
            $attributes['author_id'] = $user->id;
            $attributes['author_type'] = get_class($user);
        }
        return \Ijideals\SocialPosts\Models\Post::factory()->create($attributes);
    }
}
