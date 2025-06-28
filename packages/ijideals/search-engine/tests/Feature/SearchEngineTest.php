<?php

namespace Ijideals\SearchEngine\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\SearchEngine\Tests\TestCase;
use App\Models\User;
use Ijideals\SocialPosts\Models\Post;
use Illuminate\Support\Facades\Artisan; // For scout:import, if usable

class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected Post $post1;
    protected Post $post2;
    protected Post $post3ByUser1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = $this->createUser(['name' => 'Alice Wonderland', 'email' => 'alice@example.com']);
        $this->user2 = $this->createUser(['name' => 'Bob The Builder', 'email' => 'bob@example.com']);

        $this->post1 = $this->createPost($this->user1, ['content' => 'Laravel is a great PHP framework for web artisans.']);
        $this->post2 = $this->createPost($this->user2, ['content' => 'Another post about PHP and web development.']);
        $this->post3ByUser1 = $this->createPost($this->user1, ['content' => 'Alice writes about her adventures in Wonderland.']);

        // Import data into Scout
        // Note: In a real testing scenario with external Scout drivers, this might need careful handling.
        // For 'database' driver, makeAllSearchable() or scout:import is needed.
        // The TestCase provides an importSearchableModels helper.
        $this->importSearchableModels();
    }

    /** @test */
    public function it_can_search_for_users_by_name()
    {
        $response = $this->getJson(route('search.global', ['q' => 'Alice']));

        $response->assertStatus(200)
                 ->assertJsonPath('results.user.data.0.name', 'Alice Wonderland')
                 ->assertJsonCount(1, 'results.user.data');
    }

    /** @test */
    public function it_can_search_for_users_by_email()
    {
        $response = $this->getJson(route('search.global', ['q' => 'bob@example.com']));

        $response->assertStatus(200)
                 ->assertJsonPath('results.user.data.0.name', 'Bob The Builder')
                 ->assertJsonCount(1, 'results.user.data');
    }

    /** @test */
    public function it_can_search_for_posts_by_content()
    {
        $response = $this->getJson(route('search.global', ['q' => 'PHP framework']));

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'results.post.data') // Only post1 should match "PHP framework" precisely
                 ->assertJsonPath('results.post.data.0.content', 'Laravel is a great PHP framework for web artisans.');
    }

    /** @test */
    public function it_can_search_for_multiple_model_types()
    {
        // 'Wonderland' is in Alice's name and one of her posts
        $response = $this->getJson(route('search.global', ['q' => 'Wonderland', 'types' => 'user,post']));

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'results.user.data')
                 ->assertJsonPath('results.user.data.0.name', 'Alice Wonderland')
                 ->assertJsonCount(1, 'results.post.data')
                 ->assertJsonPath('results.post.data.0.content', 'Alice writes about her adventures in Wonderland.');
    }

    /** @test */
    public function it_returns_empty_results_for_no_match()
    {
        $response = $this->getJson(route('search.global', ['q' => 'NonExistentTermXYZ']));

        $response->assertStatus(200) // The API itself succeeds
                 ->assertJsonPath('results', []); // Expecting the 'results' object to be empty or types to have empty data
    }

    /** @test */
    public function it_handles_empty_query_string_gracefully()
    {
        $response = $this->getJson(route('search.global', ['q' => '']));
        $response->assertStatus(400) // Bad request
                 ->assertJson(['message' => 'Search query cannot be empty.']);
    }

    /** @test */
    public function it_paginates_search_results_for_a_model_type()
    {
        // Create enough posts by user1 to ensure pagination
        for ($i = 0; $i < 10; $i++) {
            $this->createPost($this->user1, ['content' => "Alice pagination test post {$i}"]);
        }
        $this->importSearchableModels(); // Re-import all

        $perPage = config('search-engine.pagination_items', 5); // Should be 5 from TestCase setup

        $response = $this->getJson(route('search.global', ['q' => 'Alice', 'types' => 'post']));

        $response->assertStatus(200)
                 ->assertJsonCount($perPage, 'results.post.data')
                 ->assertJsonPath('results.post.pagination.current_page', 1)
                 ->assertJsonPath('results.post.pagination.per_page', $perPage);
                 // Total should be 1 (original post3ByUser1) + 10 new = 11
        $this->assertEquals(11, $response->json('results.post.pagination.total'));
    }

    /** @test */
    public function search_is_case_insensitive_for_database_driver_with_like()
    {
        // This test's success depends on the database collation for LIKE queries if Scout's database
        // driver uses LIKE. SQLite's LIKE is case-insensitive by default for ASCII.
        // For MySQL, it depends on the collation of the 'content' column in 'scout_index'.

        $response = $this->getJson(route('search.global', ['q' => 'laravel'])); // lowercase

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'results.post.data')
                 ->assertJsonPath('results.post.data.0.content', 'Laravel is a great PHP framework for web artisans.');
    }
}
