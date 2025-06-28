<?php

namespace Ijideals\NotificationSystem\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User; // Main app's User model
use Ijideals\NotificationSystem\Providers\NotificationSystemServiceProvider;
use Ijideals\Likeable\Providers\LikeableServiceProvider;
use Ijideals\Commentable\Providers\CommentableServiceProvider;
use Ijideals\Followable\Providers\FollowableServiceProvider;
use Ijideals\SocialPosts\Providers\SocialPostsServiceProvider;
use Ijideals\SocialPosts\Models\Post;


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

        if (!Schema::hasTable('posts')) { // For testing like/comment on posts
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('author_id');
                $table->string('author_type');
                $table->index(['author_id', 'author_type']);
                $table->text('content');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('likes')) { // From Likeable package
            Schema::create('likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->morphs('likeable');
                $table->timestamps();
                $table->unique(['user_id', 'likeable_id', 'likeable_type']);
            });
        }

        if (!Schema::hasTable('comments')) { // From Commentable package
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->morphs('commentable');
                $table->text('content');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes(); // Assuming soft deletes are used
            });
        }

        if (!Schema::hasTable('followers')) { // From Followable package
             Schema::create('followers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('followable_id');
                $table->string('followable_type');
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                // No direct foreign key for followable_id due to morph
                $table->index(['followable_id', 'followable_type']);
                $table->unique(['user_id', 'followable_id', 'followable_type']);
            });
        }

        // Run this package's migrations (for 'notifications' table)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            NotificationSystemServiceProvider::class,
            LikeableServiceProvider::class,
            CommentableServiceProvider::class,
            FollowableServiceProvider::class,
            SocialPostsServiceProvider::class,
        ];
    }

    protected function getBasePath()
    {
        // Adjust if your package structure is different
        return dirname(__DIR__, 4) . '/social-network';
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

        // Configure this package
        $app['config']->set('notification-system.user_model', User::class);
        $app['config']->set('notification-system.notification_model', \Ijideals\NotificationSystem\Models\Notification::class);
        $app['config']->set('notification-system.event_listeners', [
            \Ijideals\Likeable\Events\ModelLiked::class => [
                \Ijideals\NotificationSystem\Listeners\SendNewLikeNotificationListener::class,
            ],
            \Ijideals\Commentable\Events\CommentPosted::class => [
                \Ijideals\NotificationSystem\Listeners\SendNewCommentNotificationListener::class,
            ],
            \Ijideals\Followable\Events\UserFollowed::class => [
                \Ijideals\NotificationSystem\Listeners\SendNewFollowerNotificationListener::class,
            ],
        ]);

        // Configure dependent packages as needed for tests
        $app['config']->set('likeable.user_model', User::class);
        $app['config']->set('likeable.like_model', \Ijideals\Likeable\Models\Like::class);
        $app['config']->set('likeable.events.model_liked', \Ijideals\Likeable\Events\ModelLiked::class);

        $app['config']->set('commentable.user_model', User::class);
        $app['config']->set('commentable.comment_model', \Ijideals\Commentable\Models\Comment::class);
        $app['config']->set('commentable.events.comment_posted', \Ijideals\Commentable\Events\CommentPosted::class);

        $app['config']->set('followable.user_model', User::class);
        $app['config']->set('followable.events.user_followed', \Ijideals\Followable\Events\UserFollowed::class);

        $app['config']->set('social-posts.user_model', User::class); // If Post model needs it
        $app['config']->set('social-posts.post_model', Post::class);


        // Ensure User model uses necessary traits for testing
        // This is a bit of a hack for package testing. Ideally, Testbench would use the app's User model.
        // Or, create a specific TestUser model within the test suite.
        if (!class_exists(\App\Models\User::class)) {
            eval("namespace App\Models; use Illuminate\Foundation\Auth\User as Authenticatable; use Illuminate\Database\Eloquent\Factories\HasFactory; use Ijideals\Likeable\Concerns\CanLike; use Ijideals\Commentable\Concerns\CanComment; use Ijideals\Followable\Followable; use Ijideals\NotificationSystem\Concerns\HasNotifications; class User extends Authenticatable { use HasFactory, CanLike, CanComment, Followable, HasNotifications; protected \$fillable = ['id', 'name', 'email', 'password']; public static function factory() { return \Database\Factories\UserFactory::new(); } }");
        }
         if (!class_exists(\Database\Factories\UserFactory::class)) {
            eval("namespace Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use App\Models\User; class UserFactory extends Factory { protected \$model = User::class; public function definition() { return ['name' => \$this->faker->name(), 'email' => \$this->faker->unique()->safeEmail(), 'password' => bcrypt('password')]; } }");
        }
        if (!class_exists(\Ijideals\SocialPosts\Database\Factories\PostFactory::class)) {
             eval("namespace Ijideals\SocialPosts\Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory; use Ijideals\SocialPosts\Models\Post; use App\Models\User; class PostFactory extends Factory { protected \$model = Post::class; public function definition() { \$author = User::factory()->create(); return ['author_id' => \$author->id, 'author_type' => get_class(\$author), 'content' => \$this->faker->paragraph]; } }");
        }


    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createPost(User $author, array $attributes = []): Post
    {
        return Post::factory()->create(array_merge(
            ['author_id' => $author->id, 'author_type' => get_class($author)],
            $attributes
        ));
    }
}
