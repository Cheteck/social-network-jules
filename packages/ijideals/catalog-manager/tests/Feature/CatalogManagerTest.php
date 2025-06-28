<?php

namespace Ijideals\CatalogManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Ijideals\CatalogManager\Tests\TestCase; // Uses the package's TestCase
use App\Models\User;
use Ijideals\ShopManager\Models\Shop;
use Ijideals\CatalogManager\Models\Category;
use Ijideals\CatalogManager\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan; // For scout:import

class CatalogManagerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // $platformAdmin, $shopOwner, $testShop are set up in parent TestCase

    protected Category $categoryElectronics;
    protected Category $categoryBooks;

    protected function setUp(): void
    {
        parent::setUp(); // This sets up users, shop, roles, permissions, and runs migrations

        // Create some global categories using the API for more realistic setup
        $this->actingAs($this->platformAdmin, 'api');
        $responseElectronics = $this->postJson(route('catalog.categories.store'), ['name' => 'Electronics', 'description' => 'Gadgets and devices']);
        $this->categoryElectronics = Category::find($responseElectronics->json('id'));

        $responseBooks = $this->postJson(route('catalog.categories.store'), ['name' => 'Books', 'description' => 'Novels, comics, etc.']);
        $this->categoryBooks = Category::find($responseBooks->json('id'));

        // Ensure morph map for Scout if products are made searchable within tests
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'shop' => Shop::class, // From ShopManager
            'product' => Product::class, // From this package
            'user' => User::class, // App user
            'post' => \Ijideals\SocialPosts\Models\Post::class, // If posts are searchable
        ]);
         // Import initial data for search if product search tests are added
        // $this->artisan('scout:import', ['model' => Product::class]);
        // $this->artisan('scout:import', ['model' => Shop::class]);
        // $this->artisan('scout:import', ['model' => User::class]);
    }

    // --- Category Management by Platform Admin ---

    /** @test */
    public function platform_admin_can_create_and_manage_categories()
    {
        $this->actingAs($this->platformAdmin, 'api');

        // Create
        $responseCreate = $this->postJson(route('catalog.categories.store'), ['name' => 'Software', 'parent_id' => $this->categoryElectronics->id]);
        $responseCreate->assertStatus(201)->assertJsonPath('name', 'Software');
        $softwareCategoryId = $responseCreate->json('id');
        $this->assertDatabaseHas('categories', ['slug' => 'software', 'parent_id' => $this->categoryElectronics->id]);

        // Read (Show)
        $this->getJson(route('catalog.categories.show', ['categorySlugOrId' => 'software']))
             ->assertStatus(200)->assertJsonPath('name', 'Software');

        // Update
        $this->putJson(route('catalog.categories.update', ['categorySlugOrId' => 'software']), ['name' => 'Utility Software'])
             ->assertStatus(200)->assertJsonPath('name', 'Utility Software');
        $this->assertDatabaseHas('categories', ['slug' => 'utility-software']);


        // Delete
        $this->deleteJson(route('catalog.categories.destroy', ['categorySlugOrId' => 'utility-software']))
             ->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['slug' => 'utility-software']);
    }

    /** @test */
    public function non_platform_admin_cannot_manage_categories()
    {
        $this->actingAs($this->shopOwner, 'api'); // Shop owner is not platform admin

        $this->postJson(route('catalog.categories.store'), ['name' => 'Attempt Category'])
             ->assertStatus(403); // Assuming a Gate/Policy is in place

        $this->putJson(route('catalog.categories.update', ['categorySlugOrId' => $this->categoryElectronics->slug]), ['name' => 'New Name'])
             ->assertStatus(403);

        $this->deleteJson(route('catalog.categories.destroy', ['categorySlugOrId' => $this->categoryElectronics->slug]))
             ->assertStatus(403);
    }


    // --- Product Management by Shop Admin/Editor ---

    /** @test */
    public function shop_admin_can_create_a_product_for_their_shop()
    {
        $this->actingAs($this->shopOwner, 'api'); // shopOwner is shop_admin for $this->testShop

        $productData = [
            'name' => 'Super TV',
            'description' => 'A very super TV.',
            'price' => 1299.99,
            'sku' => 'TV-SUPER-001',
            'stock_quantity' => 50,
            'is_active' => true,
            'category_ids' => [$this->categoryElectronics->id],
        ];

        $response = $this->postJson(route('shops.products.store', ['shopSlugOrId' => $this->testShop->slug]), $productData);
        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Super TV')
                 ->assertJsonPath('shop_id', $this->testShop->id);

        $this->assertDatabaseHas('products', ['sku' => 'TV-SUPER-001', 'shop_id' => $this->testShop->id]);
        $product = Product::where('sku', 'TV-SUPER-001')->first();
        $this->assertTrue($product->categories->contains($this->categoryElectronics));
    }

    /** @test */
    public function shop_admin_can_upload_images_when_creating_product()
    {
        $this->actingAs($this->shopOwner, 'api');
        Storage::fake(config('media-uploader.default_disk','public_test'));

        $productData = [
            'name' => 'Camera with Images', 'price' => 299.99, 'stock_quantity' => 20,
            'images' => [ UploadedFile::fake()->image('camera1.jpg'), UploadedFile::fake()->image('camera2.png') ]
        ];
        $response = $this->postJson(route('shops.products.store', ['shopSlugOrId' => $this->testShop->slug]), $productData);
        $response->assertStatus(201);
        $product = Product::find($response->json('id'));
        $this->assertCount(2, $product->getMedia(config('catalog-manager.media_collections.product_images.name')));
    }


    /** @test */
    public function shop_admin_can_update_product_in_their_shop()
    {
        $product = Product::factory()->create(['shop_id' => $this->testShop->id, 'name' => 'Old Laptop Name']);
        $this->actingAs($this->shopOwner, 'api');

        $updatedData = [
            'name' => 'New Gaming Laptop',
            'price' => 1999.99,
            'category_ids' => [$this->categoryElectronics->id, $this->categoryBooks->id] // Add to books too
        ];
        $response = $this->putJson(route('shops.products.update', [
            'shopSlugOrId' => $this->testShop->slug,
            'productSlugOrId' => $product->slug // Assuming slug is unique per shop for products
        ]), $updatedData);

        $response->assertStatus(200)->assertJsonPath('name', 'New Gaming Laptop');
        $product->refresh();
        $this->assertEquals(1999.99, $product->price);
        $this->assertTrue($product->categories->contains($this->categoryElectronics));
        $this->assertTrue($product->categories->contains($this->categoryBooks));
    }

    /** @test */
    public function shop_admin_can_delete_product_from_their_shop()
    {
        $product = Product::factory()->create(['shop_id' => $this->testShop->id]);
        $this->actingAs($this->shopOwner, 'api');

        $response = $this->deleteJson(route('shops.products.destroy', [
            'shopSlugOrId' => $this->testShop->slug,
            'productSlugOrId' => $product->slug
        ]));
        // Assuming lang files are not yet created for catalog-manager for this test run
        $response->assertStatus(200)->assertJsonPath('message', 'Product deleted successfully.');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function basic_user_cannot_create_product_in_a_shop()
    {
        $this->actingAs($this->basicUser, 'api');
         $productData = ['name' => 'Unauthorized Product', 'price' => 10];
        $response = $this->postJson(route('shops.products.store', ['shopSlugOrId' => $this->testShop->slug]), $productData);
        $response->assertStatus(403); // Or based on your authorization logic in ProductController
    }

    /** @test */
    public function products_are_listed_publicly_for_an_active_shop()
    {
        Product::factory()->count(3)->create(['shop_id' => $this->testShop->id, 'is_active' => true]);
        Product::factory()->create(['shop_id' => $this->testShop->id, 'is_active' => false]);

        $response = $this->getJson(route('shops.products.index', ['shopSlugOrId' => $this->testShop->slug]));
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data'); // Only active products
    }

    /** @test */
    public function product_can_be_viewed_publicly_if_active()
    {
        $product = Product::factory()->create(['shop_id' => $this->testShop->id, 'is_active' => true, 'name' => 'Public Product']);
        $response = $this->getJson(route('shops.products.show', ['shopSlugOrId' => $this->testShop->slug, 'productSlugOrId' => $product->slug]));
        $response->assertStatus(200)
                 ->assertJsonPath('name', 'Public Product');
    }

    /** @test */
    public function inactive_product_is_not_shown_publicly()
    {
        $product = Product::factory()->create(['shop_id' => $this->testShop->id, 'is_active' => false]);
        $response = $this->getJson(route('shops.products.show', ['shopSlugOrId' => $this->testShop->slug, 'productSlugOrId' => $product->slug]));
        $response->assertStatus(404);
    }

}
