<?php

namespace Ijideals\Followable\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
// Adjust if your User model is located elsewhere or has a different name
use App\Models\User;
use Ijideals\Followable\FollowableServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually create the users table if it doesn't exist for testbench
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

        // It's important to run migrations from the package itself
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // If your package depends on User model factory, ensure it's available
        // For simplicity, we'll use a basic User factory if needed or create users directly.
        // User::factory()->create(...) can be used if App\Models\User has a factory.
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
            FollowableServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    /**
     * Define the base path for the application during testing.
     *
     * @return string
     */
    protected function getBasePath()
    {
        // Assumes this TestCase.php is at /app/packages/ijideals/followable/tests/TestCase.php
        // __DIR__ is /app/packages/ijideals/followable/tests
        // dirname(__DIR__, 4) is /app
        // So, we need /app/social-network
        return dirname(__DIR__, 4) . '/social-network';
    }

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

        // Ensure the User model uses the Followable trait for tests.
        // This is tricky because Testbench might not pick up the main app's User model modifications.
        // A common approach is to use a local User model for tests that includes the trait.
        // For now, we assume the App\Models\User correctly uses the trait.
        // Or, we can create a local TestUser model within the test suite.
    }

    /**
     * Helper to create a user.
     * If App\Models\User has a factory, it's better to use it.
     * This is a simplified version.
     */
    protected function createUser(array $attributes = []): User
    {
        // Check if User model has a factory and use it
        if (method_exists(User::class, 'factory')) {
            return User::factory()->create($attributes);
        }

        // Basic user creation if no factory
        return User::create(array_merge([
            'name' => 'Test User ' . rand(1000,9999),
            'email' => 'test'.rand(1000,9999).'@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
