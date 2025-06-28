<?php

namespace Ijideals\UserSettings\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User; // Main app's User model
use Ijideals\UserSettings\Providers\UserSettingsServiceProvider;
// For testing notification settings integration:
use Ijideals\NotificationSystem\Providers\NotificationSystemServiceProvider;
use Ijideals\NotificationSystem\Models\Notification;
use Ijideals\Likeable\Providers\LikeableServiceProvider;
use Ijideals\Likeable\Events\ModelLiked;
use Ijideals\SocialPosts\Providers\SocialPostsServiceProvider;
use Ijideals\SocialPosts\Models\Post;


abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually set up tables
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

        // Table for ijideals/notification-system (if testing integration)
        if (!Schema::hasTable(config('notification-system.table_name', 'notifications'))) {
            Schema::create(config('notification-system.table_name', 'notifications'), function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('type');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
        // Table for ijideals/likeable (if testing integration with like notifications)
        if (!Schema::hasTable(config('likeable.table_name', 'likes'))) {
            Schema::create(config('likeable.table_name', 'likes'), function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->morphs('likeable');
                $table->timestamps();
            });
        }
        // Table for ijideals/social-posts (if testing integration with like notifications on posts)
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('author_id'); // Assuming it's constrained to users table for simplicity in test
                $table->string('author_type');
                $table->text('content');
                $table->timestamps();
            });
        }


        // Run this package's migrations ('user_settings' table)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->testUser = $this->createUser();
    }

    protected function getPackageProviders($app)
    {
        return [
            UserSettingsServiceProvider::class,
            NotificationSystemServiceProvider::class, // For integration tests
            LikeableServiceProvider::class,           // For integration tests
            SocialPostsServiceProvider::class,      // For integration tests
        ];
    }

    protected function getBasePath()
    {
        return dirname(__DIR__, 4) . '/social-network';
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.guards.api.driver', 'sanctum');


        // This package's config
        $app['config']->set('user-settings.usersetting_model', \Ijideals\UserSettings\Models\UserSetting::class);
        $app['config']->set('user-settings.defaults', [
            'notifications.new_like.database' => true,
            'notifications.new_comment.database' => true,
            'notifications.new_follower.database' => false, // Default to false for one test case
            'privacy.profile_visibility' => 'public',
        ]);
        $app['config']->set('user-settings.casts', [
            'notifications.new_like.database' => 'boolean',
            'notifications.new_comment.database' => 'boolean',
            'notifications.new_follower.database' => 'boolean',
        ]);

        // Dependent packages' config
        $app['config']->set('notification-system.user_model', User::class);
        $app['config']->set('notification-system.notification_model', Notification::class);
        $app['config']->set('notification-system.event_listeners', [
             \Ijideals\Likeable\Events\ModelLiked::class => [
                \Ijideals\NotificationSystem\Listeners\SendNewLikeNotificationListener::class,
            ],
        ]);
        $app['config']->set('likeable.user_model', User::class);
        $app['config']->set('likeable.events.model_liked', ModelLiked::class);
        $app['config']->set('social-posts.post_model', Post::class);


        // Minimal User model for testing if not found in App\Models
        // This User model needs HasSettings, CanLike, Notifiable (for notifications), HasSocialPosts traits
        if (!class_exists(\App\Models\User::class)) {
             eval("namespace App\Models;
                    use Illuminate\Foundation\Auth\User as Authenticatable;
                    use Illuminate\Database\Eloquent\Factories\HasFactory;
                    use Ijideals\UserSettings\Concerns\HasSettings;
                    use Ijideals\Likeable\Concerns\CanLike;
                    use Ijideals\Likeable\Contracts\Liker;
                    use Ijideals\SocialPosts\Concerns\HasSocialPosts;
                    // Notifiable is Laravel's built-in for notifications, but we use our custom system primarily
                    // use Illuminate\Notifications\Notifiable;

                    class User extends Authenticatable implements Liker {
                        use HasFactory, HasSettings, CanLike, HasSocialPosts;
                        protected \$fillable = ['id', 'name', 'email', 'password'];
                        public static function factory() {
                            return \Database\Factories\UserFactory::new();
                        }
                    }");
        }
         if (!class_exists(\Database\Factories\UserFactory::class)) {
             eval("namespace Database\Factories;
                    use Illuminate\Database\Eloquent\Factories\Factory;
                    use App\Models\User;
                    class UserFactory extends Factory {
                        protected \$model = User::class;
                        public function definition() {
                            return ['name' => \$this->faker->name(), 'email' => \$this->faker->unique()->safeEmail(), 'password' => bcrypt('password')];
                        }
                    }");
        }
         if (!class_exists(\Ijideals\SocialPosts\Models\Post::class)) {
            eval("namespace Ijideals\SocialPosts\Models;
                   use Illuminate\Database\Eloquent\Model;
                   use Illuminate\Database\Eloquent\Factories\HasFactory;
                   use Ijideals\Likeable\Concerns\CanBeLiked;
                   use Ijideals\Likeable\Contracts\Likeable;
                   class Post extends Model implements Likeable {
                       use HasFactory, CanBeLiked;
                       protected \$fillable = ['author_id', 'author_type', 'content'];
                       public static function factory() { return \Ijideals\SocialPosts\Database\Factories\PostFactory::new(); }
                       public function author() { return \$this->morphTo(); } // Assuming polymorphic author
                   }");
        }
        if (!class_exists(\Ijideals\SocialPosts\Database\Factories\PostFactory::class)) {
             eval("namespace Ijideals\SocialPosts\Database\Factories;
                    use Illuminate\Database\Eloquent\Factories\Factory;
                    use Ijideals\SocialPosts\Models\Post;
                    use App\Models\User;
                    class PostFactory extends Factory {
                        protected \$model = Post::class;
                        public function definition() {
                            \$author = User::factory()->create();
                            return ['author_id' => \$author->id, 'author_type' => get_class(\$author), 'content' => \$this->faker->paragraph];
                        }
                    }");
        }
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
     protected function createPostForUser(User $user, array $attributes = []): Post
    {
        return Post::factory()->create(array_merge([
            'author_id' => $user->id,
            'author_type' => get_class($user)
        ], $attributes));
    }
}
