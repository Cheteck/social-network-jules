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

        // Ensure users table exists
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Ensure posts table exists (as Post will be our test Likeable)
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                // For polymorphic relation in Post model (ijideals/social-posts)
                $table->unsignedBigInteger('author_id');
                $table->string('author_type');
                $table->index(['author_id', 'author_type']);
                $table->text('content');
                $table->timestamps();
            });
        }

        // Run package migrations (for 'likes' table)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // For testing purposes, we will assume that the User model in the main application
        // will eventually use the CanLike trait, and the Post model (from ijideals/social-posts)
        // will use the CanBeLiked trait and implement LikeableContract.
        // Unit tests for the traits themselves can use anonymous classes or test-specific stubs.
        // Feature tests will rely on this integration being set up when the main app's
        // User model and the social-posts' Post model are updated.
        // The actual application of traits will happen in the integration step.
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app)
    {
        return [
            LikeableServiceProvider::class,
            // If Post model's factory or other dependencies are needed from social-posts package
            \Ijideals\SocialPosts\Providers\SocialPostsServiceProvider::class,
        ];
    }

    /**
     * Define the base path for the application during testing.
     */
    protected function getBasePath()
    {
        // Assumes TestCase.php is at /app/packages/ijideals/likeable/tests/TestCase.php
        // This should point to /app/social-network
        return dirname(__DIR__, 4) . '/social-network';
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);

        // For API tests using Sanctum
        $app['config']->set('sanctum.middleware.encrypt_cookies', false);
    }

    /**
     * Helper to create a user.
     */
    protected function createUser(array $attributes = []): User
    {
        // Check if User model has a factory from the main app and use it
        // This requires the main app's factories to be discoverable by Testbench.
        // For simplicity, if not found, create directly.
        $userFactoryClass = '\\Database\\Factories\\UserFactory';
        if (class_exists($userFactoryClass)) {
            return $userFactoryClass::new()->create($attributes);
        }
        if (method_exists(User::class, 'factory')) {
             return User::factory()->create($attributes);
        }
        return User::create(array_merge([
            'name' => 'Test User ' . rand(1000,9999),
            'email' => 'test'.rand(1000,9999).'@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }

    /**
     * Helper to create a post.
     */
    protected function createPost(array $attributes = []): Post
    {
        if (!isset($attributes['user_id']) && !isset($attributes['author_id'])) {
            $attributes['user_id'] = $this->createUser()->id;
        }

        // If Post model has its own factory from social-posts package
        $postFactoryClass = '\\Ijideals\\SocialPosts\\Database\\Factories\\PostFactory';
        if (class_exists($postFactoryClass)) {
            return $postFactoryClass::new()->create($attributes);
        }
        if (method_exists(Post::class, 'factory')) {
            return Post::factory()->create($attributes);
        }

        return Post::create(array_merge([
            'content' => 'Test post content.',
            // 'author_id' => $attributes['user_id'] ?? $this->createUser()->id, // If social-posts uses polymorphic author
            // 'author_type' => User::class,
        ], $attributes));
    }
}
