<?php

namespace Ijideals\MediaUploader\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Ijideals\MediaUploader\Tests\TestCase;
use App\Models\User;
use Ijideals\SocialPosts\Models\Post; // Assuming Post model uses HasMedia
use Ijideals\MediaUploader\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaUploaderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->post = $this->createPost($this->user); // Create a post authored by the user

        // Ensure the morph map is set for tests
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'user' => User::class,
            'post' => Post::class,
        ]);
    }

    private function createTestFile(string $name = 'test_image.jpg', int $sizeKb = 100, string $mimeType = 'image/jpeg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 600, 400)->size($sizeKb);
    }

    /** @test */
    public function it_can_upload_a_file_to_a_model()
    {
        $this->actingAs($this->user, 'api');
        $file = $this->createTestFile();

        $response = $this->postJson(route('media.storeForModel', [
            'model_type_alias' => 'post',
            'model_id' => $this->post->id
        ]), [
            'file' => $file,
            'collection_name' => 'post_images'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('message', __('media-uploader::media-uploader.upload_success'))
                 ->assertJsonStructure(['media' => ['id', 'name', 'file_name', 'path', 'disk', 'collection_name']]);

        $media = Media::find($response->json('media.id'));
        $this->assertNotNull($media);
        Storage::disk($media->disk)->assertExists($media->path);
        $this->assertEquals('post_images', $media->collection_name);
        $this->assertEquals($this->post->id, $media->model_id);
    }

    /** @test */
    public function it_replaces_file_in_single_file_collection()
    {
        $this->actingAs($this->user, 'api');
        $file1 = $this->createTestFile('avatar1.jpg');
        $file2 = $this->createTestFile('avatar2.png');

        // Upload first avatar using the HasMedia trait method directly for this test part
        $media1 = $this->user->addMedia($file1, 'avatar');
        $this->assertNotNull($media1);
        Storage::disk($media1->disk)->assertExists($media1->path);

        // Upload second avatar to the same single-file collection via API
        $response = $this->postJson(route('media.storeForModel', [
            'model_type_alias' => 'user',
            'model_id' => $this->user->id
        ]), [
            'file' => $file2,
            'collection_name' => 'avatar'
        ]);
        $response->assertStatus(201);
        $media2_id = $response->json('media.id');
        $media2 = Media::find($media2_id);

        $this->assertNotNull($media2);
        Storage::disk($media2->disk)->assertExists($media2->path);

        $this->assertNotEquals($media1->id, $media2->id);
        Storage::disk($media1->disk)->assertMissing($media1->path);
        $this->assertDatabaseMissing('media', ['id' => $media1->id]);
        $this->assertEquals(1, $this->user->getMedia('avatar')->count());
    }

    /** @test */
    public function it_can_delete_a_media_item()
    {
        $this->actingAs($this->user, 'api');
        $file = $this->createTestFile();
        $media = $this->post->addMedia($file, 'post_images');
        Storage::disk($media->disk)->assertExists($media->path);

        $response = $this->deleteJson(route('media.destroy', ['media_id' => $media->id]));
        $response->assertStatus(200)->assertJson(['message' => __('media-uploader::media-uploader.delete_success')]);

        Storage::disk($media->disk)->assertMissing($media->path);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    /** @test */
    public function it_validates_file_size()
    {
        $this->actingAs($this->user, 'api');
        // 'default' collection in TestCase config has max_file_size = 5MB (5120 KB)
        $largeFile = UploadedFile::fake()->create('too_large.jpg', 6000); // 6MB

        $response = $this->postJson(route('media.storeForModel', ['model_type_alias' => 'post', 'model_id' => $this->post->id]), [
            'file' => $largeFile, 'collection_name' => 'default'
        ]);
        $response->assertStatus(422)
                 ->assertJsonPath('message', __('media-uploader::media-uploader.file_too_large', ['maxSizeKB' => 5120]));
    }

    /** @test */
    public function it_validates_mime_type()
    {
        $this->actingAs($this->user, 'api');
        // 'default' collection in TestCase config has allowed_mime_types = ['image/jpeg', 'image/png']
        $invalidTypeFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson(route('media.storeForModel', ['model_type_alias' => 'post', 'model_id' => $this->post->id]), [
            'file' => $invalidTypeFile, 'collection_name' => 'default'
        ]);
        $response->assertStatus(422)
                 ->assertJsonPath('message', __('media-uploader::media-uploader.invalid_mime_type', [
                    'mimeType' => 'application/pdf', // Actual mime type of the fake file
                    'allowedTypes' => implode(', ', config('media-uploader.collections.default.allowed_mime_types'))
                 ]));
    }

    /** @test */
    public function api_messages_are_translated_to_french()
    {
        $this->actingAs($this->user, 'api');
        app()->setLocale('fr');
        $file = $this->createTestFile();

        // Test upload success
        $responseUpload = $this->withHeaders(['Accept-Language' => 'fr'])
                               ->postJson(route('media.storeForModel', ['model_type_alias' => 'post', 'model_id' => $this->post->id]),
                                   ['file' => $file, 'collection_name' => 'post_images']);
        $responseUpload->assertStatus(201)
                       ->assertJsonPath('message', 'Média téléversé avec succès.');

        $mediaId = $responseUpload->json('media.id');

        // Test delete success
        $responseDelete = $this->withHeaders(['Accept-Language' => 'fr'])
                               ->deleteJson(route('media.destroy', ['media_id' => $mediaId]));
        $responseDelete->assertStatus(200)
                       ->assertJsonPath('message', 'Média supprimé avec succès.');

        // Test media not found (after deletion)
        $responseNotFound = $this->withHeaders(['Accept-Language' => 'fr'])
                                 ->deleteJson(route('media.destroy', ['media_id' => $mediaId]));
        $responseNotFound->assertStatus(404)
                         ->assertJsonPath('message', 'Média non trouvé.');

        app()->setLocale(config('app.fallback_locale', 'en')); // Reset locale
    }
}
