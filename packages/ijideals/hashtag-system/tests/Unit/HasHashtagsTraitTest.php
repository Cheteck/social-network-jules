<?php

namespace Ijideals\HashtagSystem\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ijideals\HashtagSystem\Models\Hashtag;
use Ijideals\HashtagSystem\Tests\TestSupport\Models\TestPost;
use Ijideals\HashtagSystem\Tests\TestCase;
use Ijideals\HashtagSystem\Traits\HasHashtags;
use Illuminate\Foundation\Testing\RefreshDatabase;


class HasHashtagsTraitTest extends TestCase
{
    use RefreshDatabase;

    // TestPost::migrate() is now called in TestCase::setUpDatabase()

    protected function createTestPost(array $attributes = ['title' => 'My Test Post', 'content' => 'Some content']): TestPost
    {
        return TestPost::create($attributes);
    }

    /** @test */
    public function it_can_add_a_single_hashtag_as_string()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#laravel');

        $this->assertCount(1, $post->hashtags);
        $this->assertEquals('laravel', $post->hashtags->first()->name);
        $this->assertDatabaseHas('hashtags', ['name' => 'laravel']);
    }

    /** @test */
    public function it_can_add_multiple_hashtags_as_string()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#php #laravel #livewire');

        $this->assertCount(3, $post->hashtags);
        $this->assertDatabaseHas('hashtags', ['name' => 'php']);
        $this->assertDatabaseHas('hashtags', ['name' => 'laravel']);
        $this->assertDatabaseHas('hashtags', ['name' => 'livewire']);
    }

    /** @test */
    public function it_can_add_hashtags_as_array()
    {
        $post = $this->createTestPost();
        $post->addHashtags(['#symfony', 'vuejs', '#inertia']); // Mix with and without #

        $this->assertCount(3, $post->hashtags);
        $this->assertEqualsCanonicalizing(['symfony', 'vuejs', 'inertia'], $post->hashtags->pluck('name')->all());
    }

    /** @test */
    public function it_handles_duplicate_hashtags_when_adding()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#duplicate #duplicate #tag');
        $this->assertCount(2, $post->hashtags); // duplicate, tag
    }

    /** @test */
    public function it_is_case_insensitive_when_adding_hashtags_but_stores_lowercase()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#CaSeTeSt #AnOtHeR');
        $this->assertCount(2, $post->hashtags);
        $this->assertEqualsCanonicalizing(['casetest', 'another'], $post->hashtags->pluck('name')->all());
        $this->assertDatabaseHas('hashtags', ['name' => 'casetest']);
        $this->assertDatabaseHas('hashtags', ['name' => 'another']);
    }

    /** @test */
    public function sync_hashtags_removes_old_and_adds_new_ones()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#initial #setup');
        $this->assertCount(2, $post->hashtags);

        $post->syncHashtags('#new #sync #only');
        $post->load('hashtags'); // Refresh the relationship

        $this->assertCount(3, $post->hashtags);
        $this->assertEqualsCanonicalizing(['new', 'sync', 'only'], $post->hashtags->pluck('name')->all());
        $this->assertDatabaseMissing('hashtags', ['name' => 'initial']); // Assuming it's not used by 'new', 'sync', 'only'
        $this->assertDatabaseMissing('hashtaggables', ['hashtag_id' => Hashtag::where('name', 'initial')->first()?->id, 'hashtaggable_id' => $post->id]);
    }

    /** @test */
    public function remove_hashtags_detaches_specified_tags()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#one #two #three');
        $this->assertCount(3, $post->hashtags);

        $post->removeHashtags('#one #three');
        $post->load('hashtags');

        $this->assertCount(1, $post->hashtags);
        $this->assertEquals('two', $post->hashtags->first()->name);
    }

    /** @test */
    public function remove_all_hashtags_detaches_all_tags_from_model()
    {
        $post = $this->createTestPost();
        $post->addHashtags('#one #two #three');
        $this->assertCount(3, $post->hashtags);

        $post->removeAllHashtags();
        $post->load('hashtags');

        $this->assertCount(0, $post->hashtags);
    }

    /** @test */
    public function parsing_hashtags_from_string_is_correct()
    {
        $post = $this->createTestPost();
        // Access the protected method parseHashtags via reflection for testing
        $reflection = new \ReflectionClass($post);
        $method = $reflection->getMethod('parseHashtags');
        $method->setAccessible(true);

        $parsed = $method->invokeArgs($post, ["#Test #tag with #multiple-words and #numbers123 and #alpha_numeric"]);
        $this->assertEqualsCanonicalizing(['test', 'tag', 'multiple-words', 'numbers123', 'alpha_numeric'], $parsed);

        $parsedEmpty = $method->invokeArgs($post, ["No hashtags here"]);
        $this->assertEmpty($parsedEmpty);

        $parsedComplex = $method->invokeArgs($post, ["Text before #start #middle_tag text after #end."]);
        $this->assertEqualsCanonicalizing(['start', 'middle_tag', 'end'], $parsedComplex);
    }

    /** @test */
    public function it_does_not_create_empty_or_invalid_hashtags_from_parsing()
    {
        $post = $this->createTestPost();
        $post->addHashtags("#valid # tag_with_space_is_invalid #another_valid");
        $this->assertCount(2, $post->hashtags);
        $this->assertEqualsCanonicalizing(['valid', 'another_valid'], $post->hashtags->pluck('name')->all());
        $this->assertDatabaseMissing('hashtags', ['name' => '']);
        $this->assertDatabaseMissing('hashtags', ['name' => ' ']);
    }

    /** @test */
    public function it_can_add_hashtags_from_collection_of_hashtag_models()
    {
        $post = $this->createTestPost();
        $tag1 = Hashtag::create(['name' => 'modelcollectiontag1']);
        $tag2 = Hashtag::create(['name' => 'modelcollectiontag2']);
        $tagsCollection = new \Illuminate\Support\Collection([$tag1, $tag2]);

        $post->addHashtags($tagsCollection);
        $this->assertCount(2, $post->hashtags);
        $this->assertEqualsCanonicalizing(['modelcollectiontag1', 'modelcollectiontag2'], $post->hashtags->pluck('name')->all());
    }

    /** @test */
    public function it_can_add_hashtags_from_collection_of_strings()
    {
        $post = $this->createTestPost();
        $tagsCollection = new \Illuminate\Support\Collection(['#stringcollection1', 'stringcollection2']);

        $post->addHashtags($tagsCollection);
        $this->assertCount(2, $post->hashtags);
        $this->assertEqualsCanonicalizing(['stringcollection1', 'stringcollection2'], $post->hashtags->pluck('name')->all());
    }

}
