<?php

namespace Ijideals\SearchEngine\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User; // Main app's User model
use Ijideals\SocialPosts\Models\Post; // Example searchable model
use Ijideals\SearchEngine\Providers\SearchEngineServiceProvider;
use Laravel\Scout\ScoutServiceProvider; // Scout's own ServiceProvider
use Ijideals\SocialPosts\Providers\SocialPostsServiceProvider; // For Post model & factory

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually set up tables for User and Post
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

        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('author_id');
                $table->string('author_type');
                $table->index(['author_id', 'author_type']);
                $table->text('content');
                $table->timestamps();
            });
        }

        // Scout index table (as created by our migration)
        if (!Schema::hasTable('scout_index')) {
            Schema::create('scout_index', function (Blueprint $table) {
                $table->id();
                $table->string('index_name');
                $table->unsignedBigInteger('document_id');
                $table->text('content');
                $table->timestamps();
                $table->index(['index_name', 'document_id']);
                // Note: FULLTEXT index for MySQL is omitted here for simplicity in SQLite test env
            });
        }

        // Make sure User and Post models use the Searchable trait
        // This is done in the main application code, but for tests, ensure they are.
        // We'll assume they are correctly modified as per previous plan steps.
    }

    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class, // Scout must be registered
            SearchEngineServiceProvider::class,
            SocialPostsServiceProvider::class, // For Post model/factory
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

        // Scout
        $app['config']->set('scout.driver', 'database');
        $app['config']->set('scout.database.table', 'scout_index');
        $app['config']->set('scout.queue', false); // Run indexing synchronously for tests

        // Auth & Models
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('search-engine.searchable_models', [
            'user' => User::class,
            'post' => Post::class,
        ]);
        $app['config']->set('search-engine.pagination_items', 5); // Smaller for easier test assertions

        // Ensure User and Post models are available and use factories for test data creation
        // These evals are a workaround for package testing if app models/factories aren't discoverable
        // It's better if Testbench can correctly resolve these from the main app structure.
        if (!class_exists(\App\Models\User::class)) {
             eval("namespace App\Models; use Illuminate\Foundation\Auth\User as Authenticatable; use Illuminate\Database\Eloquent\Factories\HasFactory; use Laravel\Scout\Searchable; class User extends Authenticatable { use HasFactory, Searchable; protected \$fillable = ['name', 'email', 'password']; public static function factory() { return \Database\Factories\UserFactory::new(); } public function toSearchableArray() { return ['id' => \$this->id, 'name' => \$this->name, 'email' => \$this->email];} public function searchableAs() { return 'users_index';}}");
        }
        if (!class_exists(\Database\Factories\UserFactory::class)) {
             eval("namespace Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use App\Models\User; class UserFactory extends Factory { protected \$model = User::class; public function definition() { return ['name' => \$this->faker->name(), 'email' => \$this->faker->unique()->safeEmail(), 'password' => bcrypt('password')]; } }");
        }
        if (!class_exists(\Ijideals\SocialPosts\Models\Post::class)) {
            // This is more complex as Post has its own dependencies.
            // For now, assume Post model is correctly loaded via its service provider.
            // If not, a similar eval for Post and its factory would be needed.
        }
         if (!method_exists(\Ijideals\SocialPosts\Models\Post::class, 'factory')) {
            // Minimal Post factory if not available from the package
            if (class_exists(\Ijideals\SocialPosts\Models\Post::class) && !class_exists(\Ijideals\SocialPosts\Database\Factories\PostFactory::class)) {
                 eval("namespace Ijideals\SocialPosts\Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use Ijideals\SocialPosts\Models\Post; use App\Models\User; class PostFactory extends Factory { protected \$model = Post::class; public function definition() { \$author = User::factory()->create(); return ['author_id' => \$author->id, 'author_type' => get_class(\$author), 'content' => \$this->faker->paragraph]; } }");
                 eval("namespace Ijideals\SocialPosts\Models; trait HasFactoryTrait { public static function factory() { return \Ijideals\SocialPosts\Database\Factories\PostFactory::new(); } }");
                 // This is hacky; ideally the Post package provides its factory correctly for testing contexts.
                 // For Post model: Post::mixin(new \Ijideals\SocialPosts\Models\HasFactoryTrait); (This doesn't work for static method)
                 // A better way would be to ensure Post's own tests/setup makes its factory available.
            }
        }
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createPost(User $author, array $attributes = []): Post
    {
         if(!method_exists(Post::class, 'factory')) {
            // Fallback if factory trait wasn't successfully mixed in above.
            return Post::create(array_merge(
                ['author_id' => $author->id, 'author_type' => get_class($author), 'content' => $this->faker->paragraph],
                $attributes
            ));
        }
        return Post::factory()->create(array_merge(
            ['author_id' => $author->id, 'author_type' => get_class($author)],
            $attributes
        ));
    }

    /**
     * Helper to import all searchable models into Scout.
     */
    protected function importSearchableModels()
    {
        $searchableModels = config('search-engine.searchable_models', []);
        foreach ($searchableModels as $modelClass) {
            if (class_exists($modelClass) && method_exists($modelClass, 'makeAllSearchable')) {
                // $this->artisan('scout:import', ['model' => $modelClass]); // This doesn't work well in unit tests without full app bootstrap
                // Manually trigger import for testing with database driver
                $modelClass::makeAllSearchable();
            }
        }
    }
}
