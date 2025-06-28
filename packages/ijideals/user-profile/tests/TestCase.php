<?php

namespace Ijideals\UserProfile\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User; // Main app's User model
use Ijideals\UserProfile\Providers\UserProfileServiceProvider;
use Ijideals\UserProfile\Concerns\HasProfile; // Trait to be tested on User model

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure users table exists for testbench
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

        // Run package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Dynamically add HasProfile trait to the User model for tests
        // This is a common way to ensure the User model used in tests has the necessary trait
        if (!method_exists(User::class, 'userProfile')) { // Check if trait is already applied (e.g. by test setup)
            User::mixin(new class {
                use HasProfile;
            });
        }
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            UserProfileServiceProvider::class,
            // If your package depends on other service providers from your app or other packages, list them here.
            // For example, if HasProfile needed FollowableServiceProvider to be loaded first (it doesn't here):
            // \Ijideals\Followable\FollowableServiceProvider::class
        ];
    }

    /**
     * Define the base path for the application during testing.
     * This points to the main Laravel application root.
     *
     * @return string
     */
    protected function getBasePath()
    {
        // Assumes TestCase.php is at /app/packages/ijideals/user-profile/tests/TestCase.php
        // This should point to /app/social-network
        return dirname(__DIR__, 4) . '/social-network';
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set up the User model for auth
        $app['config']->set('auth.providers.users.model', User::class);

        // Ensure Sanctum (or other auth) migrations are run if your API tests need them
        // For example, if you use personal_access_tokens table for Sanctum.
        // Usually, Testbench handles some of this if laravel/framework is a dev-dependency.
        // If not, you might need to load them manually:
        // include_once __DIR__.'/../../../../social-network/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php';
        // (new \CreatePersonalAccessTokensTable)->up();
    }

    /**
     * Helper to create a user.
     */
    protected function createUser(array $attributes = []): User
    {
        if (!class_exists(\Database\Factories\UserFactory::class) && method_exists(User::class, 'factory')) {
             return User::factory()->create($attributes);
        }

        return User::create(array_merge([
            'name' => 'Test User ' . rand(1000,9999),
            'email' => 'test'.rand(1000,9999).'@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
