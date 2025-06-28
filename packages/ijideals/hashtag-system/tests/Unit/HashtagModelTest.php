<?php

namespace Ijideals\HashtagSystem\Tests\Unit;

use Ijideals\HashtagSystem\Models\Hashtag;
use Ijideals\HashtagSystem\Tests\TestCase; // Make sure this path is correct
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class HashtagModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_hashtag()
    {
        $name = "Laravel Package";
        $slug = Str::slug($name);

        $hashtag = Hashtag::create([
            'name' => $name,
        ]);

        $this->assertInstanceOf(Hashtag::class, $hashtag);
        $this->assertEquals($name, $hashtag->name);
        $this->assertEquals($slug, $hashtag->slug);
        $this->assertDatabaseHas('hashtags', ['name' => $name, 'slug' => $slug]);
    }

    /** @test */
    public function it_automatically_generates_a_slug_if_not_provided()
    {
        $name = "Auto Slug Test";
        $hashtag = Hashtag::create(['name' => $name]);
        $this->assertEquals(Str::slug($name), $hashtag->slug);
    }

    /** @test */
    public function it_uses_provided_slug_if_available()
    {
        $name = "Manual Slug";
        $slug = "custom-manual-slug";
        $hashtag = Hashtag::create(['name' => $name, 'slug' => $slug]);
        $this->assertEquals($slug, $hashtag->slug);
    }

    /** @test */
    public function name_is_unique()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Hashtag::create(['name' => 'unique-tag', 'slug' => 'unique-tag-slug']);
        Hashtag::create(['name' => 'unique-tag', 'slug' => 'another-slug']); // This should fail
    }

    /** @test */
    public function slug_is_unique()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Hashtag::create(['name' => 'another-tag', 'slug' => 'unique-slug']);
        Hashtag::create(['name' => 'yet-another-tag', 'slug' => 'unique-slug']); // This should fail
    }

    /** @test */
    public function it_can_update_name_and_slug()
    {
        $hashtag = Hashtag::create(['name' => 'Initial Name']);
        $newName = 'Updated Name';
        $newSlug = 'updated-name-slug';

        $hashtag->update(['name' => $newName, 'slug' => $newSlug]);

        $this->assertEquals($newName, $hashtag->fresh()->name);
        $this->assertEquals($newSlug, $hashtag->fresh()->slug);
    }

    /** @test */
    public function updating_name_regenerates_slug_if_slug_is_not_provided_or_is_emptied()
    {
        $hashtag = Hashtag::create(['name' => 'Original Name']);
        $newName = 'New Name For Slug';

        $hashtag->update(['name' => $newName, 'slug' => null]);
        $this->assertEquals(Str::slug($newName), $hashtag->fresh()->slug);

        $hashtag->update(['name' => 'Another New Name', 'slug' => '']);
        $this->assertEquals(Str::slug('Another New Name'), $hashtag->fresh()->slug);
    }

}
