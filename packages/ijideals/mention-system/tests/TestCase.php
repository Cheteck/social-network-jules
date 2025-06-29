<?php

namespace IJIDeals\MentionSystem\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use IJIDeals\MentionSystem\Providers\MentionSystemServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; // Assuming main app User model

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            MentionSystemServiceProvider::class,
            // If your package depends on other service providers from your app or other packages, list them here.
            // e.g., \App\Providers\EventServiceProvider::class if events are not auto-discovered for tests
        ];
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

        // Need to ensure the users table exists for foreign key constraints
        // and for creating User instances in tests.
        // This schema definition should match your main application's users table minimally.
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique(); // Crucial for @username mentions
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // If User model uses factories from the main app, ensure they are loaded
        // $this->withFactories(__DIR__.'/../../../../database/factories'); // Adjust path as needed

        // If your package's migrations depend on other migrations (e.g. from other packages),
        // you might need to run them here or ensure they are loaded by Testbench.
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Manually load package migrations because we're not publishing them in boot for tests
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // If you need to run migrations from the main app or other packages:
        // $this->loadLaravelMigrations(); // For default Laravel migrations
        // $this->loadMigrationsFrom(base_path('database/migrations/main_app_specific'));
    }

    /**
     * Helper to create a user.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    protected function createUser(array $attributes = []): User
    {
        // If User factory is available and configured:
        // return User::factory()->create($attributes);

        // Manual creation if factory is not set up for testbench context:
        return User::create(array_merge([
            'name' => 'Test User',
            'username' => 'testuser' . rand(1000,9999), // Ensure unique username
            'email' => 'test' . rand(1000,9999) . '@example.com', // Ensure unique email
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
