<?php

namespace Ijideals\CatalogManager\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\CatalogManager\Providers\CatalogManagerServiceProvider;
use Ijideals\ShopManager\Providers\ShopManagerServiceProvider; // Dependency for Shop model
use Ijideals\MediaUploader\Providers\MediaUploaderServiceProvider; // Dependency for media
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use App\Models\User; // Assuming base User model might be needed for factories

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
            CatalogManagerServiceProvider::class,
            ShopManagerServiceProvider::class, // Added dependency
            MediaUploaderServiceProvider::class, // Added dependency
            // Add other necessary service providers for related packages if their models/services are used
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

        // Ensure spatie.permission config is loaded if shop-manager relies on it for roles
        $app['config']->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
         $app['config']->set('permission.column_names', [
            'role_pivot_key' => null,
            'permission_pivot_key' => null,
            'team_foreign_key' => 'team_id', // or 'shop_id' if shop-manager overrides it
            'model_morph_key' => 'model_id',
        ]);
         $app['config']->set('permission.teams', true); // Important for shop-manager roles
    }

    protected function setUpDatabase($app)
    {
        // Load default Laravel migrations
        $this->loadLaravelMigrations(['--database' => 'testbench']);

        // Load migrations from dependent packages first if necessary
        // This requires knowing the correct paths or having them publishable and loaded
        $this->loadMigrationsFrom(__DIR__ . '/../../../../database/migrations'); // Main app migrations (like users)
        $this->loadMigrationsFrom(__DIR__ . '/../../shop-manager/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../../media-uploader/database/migrations'); // If MediaUploader has migrations

        // Load this package's migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        // Seed roles and permissions if shop-manager relies on them
        if (class_exists(\Ijideals\ShopManager\Database\Seeders\RolesAndPermissionsSeeder::class)) {
            $this->seed(\Ijideals\ShopManager\Database\Seeders\RolesAndPermissionsSeeder::class);
        }
    }

    // Helper method to create a user
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
}
