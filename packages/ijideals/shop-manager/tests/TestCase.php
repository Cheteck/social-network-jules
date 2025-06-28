<?php

namespace Ijideals\ShopManager\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User; // Main app's User model
use Ijideals\ShopManager\Providers\ShopManagerServiceProvider;
use Spatie\Permission\PermissionServiceProvider; // Spatie's ServiceProvider
use Ijideals\MediaUploader\Providers\MediaUploaderServiceProvider; // For shop logo/cover
use Ijideals\SocialPosts\Providers\SocialPostsServiceProvider; // For shop posts
use Ijideals\SocialPosts\Models\Post; // For shop posts
use Ijideals\MediaUploader\Models\Media; // For media table
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually set up tables for dependencies
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

        // Spatie permissions tables (simplified, actual migrations are more complex)
        // The real migrations should be run by publishing Spatie's migrations.
        // Here, we ensure they exist for the test environment if not published.
        if (!Schema::hasTable(config('permission.table_names.permissions'))) {
            Schema::create(config('permission.table_names.permissions'), function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        if (!Schema::hasTable(config('permission.table_names.roles'))) {
            Schema::create(config('permission.table_names.roles'), function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'))->nullable(); // shop_id
                $table->timestamps();
                $table->unique(['name', 'guard_name', config('permission.column_names.team_foreign_key')]);
            });
        }
        if (!Schema::hasTable(config('permission.table_names.model_has_permissions'))) {
             Schema::create(config('permission.table_names.model_has_permissions'), function (Blueprint $table) {
                $table->unsignedBigInteger(PermissionRegistrar::$pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger(config('permission.column_names.model_morph_key'));
                $table->index([config('permission.column_names.model_morph_key'), 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'))->nullable(); // shop_id
                $table->foreign(PermissionRegistrar::$pivotPermission)->references('id')->on(config('permission.table_names.permissions'))->onDelete('cascade');
                $table->primary([config('permission.column_names.team_foreign_key'), PermissionRegistrar::$pivotPermission, config('permission.column_names.model_morph_key'), 'model_type'],
                        'model_has_permissions_permission_model_type_primary');
            });
        }
        if (!Schema::hasTable(config('permission.table_names.model_has_roles'))) {
            Schema::create(config('permission.table_names.model_has_roles'), function (Blueprint $table) {
                $table->unsignedBigInteger(PermissionRegistrar::$pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger(config('permission.column_names.model_morph_key'));
                $table->index([config('permission.column_names.model_morph_key'), 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->unsignedBigInteger(config('permission.column_names.team_foreign_key'))->nullable(); // shop_id
                $table->foreign(PermissionRegistrar::$pivotRole)->references('id')->on(config('permission.table_names.roles'))->onDelete('cascade');
                $table->primary([config('permission.column_names.team_foreign_key'), PermissionRegistrar::$pivotRole, config('permission.column_names.model_morph_key'), 'model_type'],
                        'model_has_roles_role_model_type_primary');
            });
        }
        if (!Schema::hasTable(config('permission.table_names.role_has_permissions'))) {
            Schema::create(config('permission.table_names.role_has_permissions'), function (Blueprint $table) {
                $table->unsignedBigInteger(PermissionRegistrar::$pivotPermission);
                $table->unsignedBigInteger(PermissionRegistrar::$pivotRole);
                $table->foreign(PermissionRegistrar::$pivotPermission)->references('id')->on(config('permission.table_names.permissions'))->onDelete('cascade');
                $table->foreign(PermissionRegistrar::$pivotRole)->references('id')->on(config('permission.table_names.roles'))->onDelete('cascade');
                $table->primary([PermissionRegistrar::$pivotPermission, PermissionRegistrar::$pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        }


        if (!Schema::hasTable('media')) { // From MediaUploader
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->morphs('model');
                $table->string('collection_name')->default('default');
                $table->string('name');
                $table->string('file_name');
                $table->string('path');
                $table->string('disk');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size');
                $table->json('manipulations')->nullable();
                $table->json('properties')->nullable();
                $table->unsignedInteger('order_column')->nullable()->index();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('posts')) { // For shop posts
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('author_id');
                $table->string('author_type');
                $table->index(['author_id', 'author_type']);
                $table->text('content');
                $table->timestamps();
            });
        }


        // Run this package's migrations (for 'shops' table)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Seed the roles and permissions
        $this->seed(\Ijideals\ShopManager\Database\Seeders\RolesAndPermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();


    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class, // Spatie's provider
            MediaUploaderServiceProvider::class,
            SocialPostsServiceProvider::class,
            ShopManagerServiceProvider::class,
        ];
    }

    protected function getBasePath()
    {
        return dirname(__DIR__, 4) . '/social-network';
    }

    protected function getEnvironmentSetUp($app)
    {
        // Database
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Auth & Models
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('shop-manager.user_model', User::class);
        $app['config']->set('shop-manager.shop_model', \Ijideals\ShopManager\Models\Shop::class);
        $app['config']->set('shop-manager.post_model_class', Post::class);


        // Spatie Permissions
        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
        $app['config']->set('permission.column_names.team_foreign_key', 'shop_id');
        $app['config']->set('permission.teams', true);
        $app['config']->set('permission.teams_foreign_key_null_when_no_team', true); // For global roles

        // Media Uploader
        $app['config']->set('media-uploader.media_model', Media::class);
        $app['config']->set('media-uploader.default_disk', 'public_test');
         $app['config']->set('filesystems.disks.public_test', [ // Ensure this disk is configured for tests
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/public_test'),
        ]);
        Storage::fake('public_test');


        // Ensure User model uses necessary traits for testing
        if (!class_exists(\App\Models\User::class)) {
             eval("namespace App\Models; use Illuminate\Foundation\Auth\User as Authenticatable; use Illuminate\Database\Eloquent\Factories\HasFactory; use Spatie\Permission\Traits\HasRoles; use Ijideals\MediaUploader\Concerns\HasMedia; class User extends Authenticatable { use HasFactory, HasRoles, HasMedia; protected \$fillable = ['name', 'email', 'password']; protected \$guard_name = 'api'; public static function factory() { return \Database\Factories\UserFactory::new(); } public function shopsOwned() { return \$this->hasMany(\Ijideals\ShopManager\Models\Shop::class, 'owner_id');} public function getShopRoleNames(\Ijideals\ShopManager\Models\Shop \$shop): \Illuminate\Support\Collection { return \$this->roles()->wherePivot(config('permission.column_names.team_foreign_key'), \$shop->id)->pluck('name'); } public function hasShopRole(\$r, \$s){ return false;} }"); // Simplified HasRoles
        }
         if (!class_exists(\Database\Factories\UserFactory::class)) {
             eval("namespace Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use App\Models\User; class UserFactory extends Factory { protected \$model = User::class; public function definition() { return ['name' => \$this->faker->name(), 'email' => \$this->faker->unique()->safeEmail(), 'password' => bcrypt('password')]; } }");
        }
        if (!class_exists(\Ijideals\SocialPosts\Models\Post::class)) {
            eval("namespace Ijideals\SocialPosts\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory; class Post extends Model { use HasFactory; protected \$fillable = ['author_id', 'author_type', 'content']; public static function factory() { return \Ijideals\SocialPosts\Database\Factories\PostFactory::new(); } public function author() { return \$this->morphTo();} }");
        }
        if (!class_exists(\Ijideals\SocialPosts\Database\Factories\PostFactory::class)) {
             eval("namespace Ijideals\SocialPosts\Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use Ijideals\SocialPosts\Models\Post; use App\Models\User; class PostFactory extends Factory { protected \$model = Post::class; public function definition() { \$author = User::factory()->create(); return ['author_id' => \$author->id, 'author_type' => get_class(\$author), 'content' => \$this->faker->paragraph]; } }");
        }
         if (!class_exists(\Ijideals\ShopManager\Models\Shop::class)) { // Should be loaded by package
            // eval for Shop if necessary
        }
        if (!method_exists(\Ijideals\ShopManager\Models\Shop::class, 'factory')) {
            if (class_exists(\Ijideals\ShopManager\Models\Shop::class) && !class_exists(\Ijideals\ShopManager\Database\Factories\ShopFactory::class)) {
                eval("namespace Ijideals\ShopManager\Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use Ijideals\ShopManager\Models\Shop; use App\Models\User; class ShopFactory extends Factory { protected \$model = Shop::class; public function definition() { return ['name' => \$this->faker->company, 'owner_id' => User::factory(), 'description' => \$this->faker->paragraph, 'is_active' => true]; } }");
            }
        }

    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createShop(User $owner, array $attributes = []): \Ijideals\ShopManager\Models\Shop
    {
        $shopModel = config('shop-manager.shop_model');
        if (method_exists($shopModel, 'factory')) {
            return $shopModel::factory()->create(array_merge(['owner_id' => $owner->id], $attributes));
        }
        return $shopModel::create(array_merge([
            'name' => $this->faker->company,
            'owner_id' => $owner->id,
            'description' => $this->faker->paragraph,
            'is_active' => true,
        ], $attributes));
    }
}
