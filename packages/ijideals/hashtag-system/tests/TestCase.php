<?php

namespace Ijideals\HashtagSystem\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\HashtagSystem\Providers\HashtagSystemServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase; // Using Testbench for package testing

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // It's common to set up in-memory SQLite for package testing
        $this->setUpDatabase($this->app);

        // Optionally, run migrations if not handled by RefreshDatabase or if specific to package
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate')->run();


        // If you have factories specific to this package, you might want to register them
        // $this->withFactories(__DIR__.'/../database/factories');
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
            HashtagSystemServiceProvider::class,
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

        // Optional: Define routes for testing if your package relies on app routes not defined in the package itself
        // For API testing, ensure the 'api' guard is configured if needed
        // $app['config']->set('auth.guards.api', ['driver' => 'token', 'provider' => 'users']);
    }

    /**
     * Set up the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        // It's good practice to ensure migrations from the main app that might be
        // dependencies (like users table) are also run if needed.
        // For this package, it primarily needs its own tables.

        // Load this package's migrations.
        $this->loadLaravelMigrations(['--database' => 'testbench']); // Loads default Laravel migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations'); // Load package's own migrations

        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        // Migrate the test model's table
        \Ijideals\HashtagSystem\Tests\TestSupport\Models\TestPost::migrate();
    }
}
