<?php

namespace Ijideals\Commentable\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Ijideals\Commentable\Tests\TestCase; // This should be the package's base TestCase
use App\Models\User;
use Ijideals\SocialPosts\Models\Post; // Example Commentable model
use Ijideals\Commentable\Models\Comment;

class CommentableTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $post;

    protected function setUp(): void
    {
        parent::setUp(); // This will call the setup in package's TestCase

        // Manually register the morph map for tests if not already done
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'post' => Post::class,
            // Add other commentable models if they are used in tests
        ]);

        // Ensure config for the package is loaded
        config(['commentable.user_model' => User::class]);
        config(['commentable.comment_model' => Comment::class]);
        config(['commentable.table_name' => 'comments']);
        config(['commentable.nested_comments' => true]); // Enable nesting for relevant tests
        config(['commentable.soft_deletes' => true]); // Enable soft deletes for relevant tests


        $this->user = $this->createUser(); // Using helper from base TestCase

        // Create a post using the social-posts package factory or simple way
        $this->post = $this->createPost(['author_id' => $this->user->id, 'author_type' => get_class($this->user)]);
    }

    /** @test */
    public function a_user_can_post_a_comment_on_a_post()
    {
        $this->actingAs($this->user, 'api');
        $content = $this->faker->paragraph;

        $response = $this->postJson(route('comments.store', ['commentable_type' => 'post', 'commentable_id' => $this->post->id]), [
            'content' => $content,
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['content' => $content])
                 ->assertJsonPath('comment.commenter.id', $this->user->id)
                 ->assertJsonPath('message', __('commentable::commentable.successfully_posted'));


        $this->assertDatabaseHas('comments', [
            'commentable_type' => $this->post->getMorphClass(),
            'commentable_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => $content,
            'parent_id' => null,
        ]);
        $this->assertEquals(1, $this->post->comments()->count());
    }

    /** @test */
    public function a_user_can_reply_to_a_comment()
    {
        $this->actingAs($this->user, 'api');
        $parentComment = $this->post->addComment($this->faker->sentence, $this->user);

        $replyContent = $this->faker->paragraph;
        $response = $this->postJson(route('comments.store', ['commentable_type' => 'post', 'commentable_id' => $this->post->id]), [
            'content' => $replyContent,
            'parent_id' => $parentComment->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['content' => $replyContent])
                 ->assertJsonPath('comment.parent_id', $parentComment->id);

        $this->assertDatabaseHas('comments', [
            'content' => $replyContent,
            'parent_id' => $parentComment->id,
        ]);
        $this->assertEquals(1, $parentComment->refresh()->replies()->count());
    }

    /** @test */
    public function a_user_can_update_their_own_comment()
    {
        $this->actingAs($this->user, 'api');
        $comment = $this->post->addComment($this->faker->sentence, $this->user);
        $updatedContent = "This is the updated content.";

        $response = $this->putJson(route('comments.update', ['comment_id' => $comment->id]), [
            'content' => $updatedContent,
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['content' => $updatedContent])
                 ->assertJsonPath('message', __('commentable::commentable.successfully_updated'));
        $this->assertEquals($updatedContent, $comment->refresh()->content);
    }

    /** @test */
    public function a_user_cannot_update_another_users_comment()
    {
        $anotherUser = $this->createUser();
        $comment = $this->post->addComment($this->faker->sentence, $anotherUser); // Comment by another user

        $this->actingAs($this->user, 'api'); // Current user tries to update
        $response = $this->putJson(route('comments.update', ['comment_id' => $comment->id]), [
            'content' => "Malicious update attempt",
        ]);
        $response->assertStatus(403)
                 ->assertJsonPath('message', __('commentable::commentable.ownership_error'));
    }

    /** @test */
    public function a_user_can_delete_their_own_comment()
    {
        $this->actingAs($this->user, 'api');
        $comment = $this->post->addComment($this->faker->sentence, $this->user);

        $response = $this->deleteJson(route('comments.destroy', ['comment_id' => $comment->id]));
        $response->assertStatus(200)
                 ->assertJson(['message' => __('commentable::commentable.successfully_deleted')]);

        if (config('commentable.soft_deletes')) {
            $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        } else {
            $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        }
    }

    /** @test */
    public function deleting_a_parent_comment_also_deletes_replies_if_cascading()
    {
        // This depends on DB foreign key `onDelete('cascade')` for parent_id
        // or logic within Comment model's deleting event.
        // The migration sets onDelete('cascade') for parent_id.

        $this->actingAs($this->user, 'api');
        $parentComment = $this->post->addComment("Parent comment", $this->user);
        $reply1 = $this->post->addComment("First reply", $this->user, $parentComment);
        $reply2 = $this->post->addComment("Second reply", $this->user, $parentComment);

        $this->assertCount(2, $parentComment->refresh()->replies);

        $this->deleteJson(route('comments.destroy', ['comment_id' => $parentComment->id]));

        if (config('commentable.soft_deletes')) {
            $this->assertSoftDeleted('comments', ['id' => $parentComment->id]);
            $this->assertSoftDeleted('comments', ['id' => $reply1->id]);
            $this->assertSoftDeleted('comments', ['id' => $reply2->id]);
        } else {
            $this->assertDatabaseMissing('comments', ['id' => $parentComment->id]);
            $this->assertDatabaseMissing('comments', ['id' => $reply1->id]);
            $this->assertDatabaseMissing('comments', ['id' => $reply2->id]);
        }
    }

    /** @test */
    public function guests_can_view_comments()
    {
        $this->post->addComment("A public comment", $this->user);

        $response = $this->getJson(route('comments.index', ['commentable_type' => 'post', 'commentable_id' => $this->post->id]));
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data'); // Assuming pagination
    }

    /** @test */
    public function comments_are_deleted_when_commentable_model_is_deleted()
    {
        $comment1 = $this->post->addComment("Test comment 1", $this->user);
        $comment2 = $this->post->addComment("Test comment 2", $this->user);

        $this->assertCount(2, $this->post->comments);

        $this->post->delete(); // Triggers bootCanBeCommentedOn

        if (config('commentable.soft_deletes')) {
            $this->assertSoftDeleted('comments', ['id' => $comment1->id]);
            $this->assertSoftDeleted('comments', ['id' => $comment2->id]);
        } else {
            $this->assertDatabaseMissing('comments', ['id' => $comment1->id]);
            $this->assertDatabaseMissing('comments', ['id' => $comment2->id]);
        }
    }

    /** @test */
    public function api_messages_are_translated_to_french()
    {
        $this->actingAs($this->user, 'api');
        app()->setLocale('fr');

        // Test posting a comment
        $content = "Un commentaire en français.";
        $responseStore = $this->withHeaders(['Accept-Language' => 'fr'])
                              ->postJson(route('comments.store', ['commentable_type' => 'post', 'commentable_id' => $this->post->id]), ['content' => $content]);
        $responseStore->assertStatus(201)
                      ->assertJsonPath('message', 'Commentaire posté avec succès.');

        $commentId = $responseStore->json('comment.id');

        // Test updating a comment
        $updatedContent = "Contenu mis à jour en français.";
        $responseUpdate = $this->withHeaders(['Accept-Language' => 'fr'])
                               ->putJson(route('comments.update', ['comment_id' => $commentId]), ['content' => $updatedContent]);
        $responseUpdate->assertStatus(200)
                       ->assertJsonPath('message', 'Commentaire mis à jour avec succès.');

        // Test deleting a comment
        $responseDelete = $this->withHeaders(['Accept-Language' => 'fr'])
                               ->deleteJson(route('comments.destroy', ['comment_id' => $commentId]));
        $responseDelete->assertStatus(200)
                       ->assertJsonPath('message', 'Commentaire supprimé avec succès.');

        // Test comment not found
        $responseNotFound = $this->withHeaders(['Accept-Language' => 'fr'])
                                 ->deleteJson(route('comments.destroy', ['comment_id' => $commentId])); // Try deleting again
        $responseNotFound->assertStatus(404)
                         ->assertJsonPath('message', 'Commentaire non trouvé.');

        app()->setLocale(config('app.fallback_locale', 'en')); // Reset locale
    }
}
