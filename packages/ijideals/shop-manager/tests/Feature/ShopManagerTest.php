<?php

namespace Ijideals\ShopManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ijideals\ShopManager\Tests\TestCase;
use App\Models\User;
use Ijideals\ShopManager\Models\Shop;
use Ijideals\SocialPosts\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class ShopManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformAdmin;
    protected User $shopOwnerA;
    protected User $shopEditorA;
    protected User $basicUser;
    protected Shop $shopA;

    protected function setUp(): void
    {
        parent::setUp(); // This runs migrations and seeds RolesAndPermissionsSeeder

        $this->platformAdmin = $this->createUser(['name' => 'Platform Admin']);
        $this->platformAdmin->assignRole('platform_admin'); // Global role

        $this->shopOwnerA = $this->createUser(['name' => 'Shop Owner A']);
        $this->shopEditorA = $this->createUser(['name' => 'Shop Editor A']);
        $this->basicUser = $this->createUser(['name' => 'Basic User']);

        // Create shop A, ownerA will be assigned 'shop_admin' role for this shop by ShopController::store
        $this->actingAs($this->shopOwnerA, 'api');
        $response = $this->postJson(route('shops.store'), [
            'name' => 'Alice Wonderland Shop',
            'description' => 'Curiouser and curiouser things!',
        ]);
        $response->assertStatus(201);
        $this->shopA = app(config('shop-manager.shop_model'))->find($response->json('id'));

        // Manually assign shop_editorA to shopA as editor for some tests
        $editorRole = Role::findByName('shop_editor', 'api'); // Assuming 'api' guard for tests
        $this->shopEditorA->assignRole($editorRole->name); // Assign role globally first (Spatie requirement)
        // Then, scope it to the shop (team)
        // The addMember method in ShopMemberController or Shop model should handle this.
        // For testing setup, we can do it more directly if needed or use the API.
        // Let's assume ShopMemberController will be tested for this.
        // For now, shopOwnerA is shop_admin of shopA.
    }

    /** @test */
    public function shop_owner_can_create_a_shop_and_is_assigned_shop_admin_role_for_it()
    {
        $this->actingAs($this->shopOwnerA, 'api');
        $shopData = [
            'name' => 'New Test Shop by OwnerA',
            'description' => 'A brand new shop.',
        ];
        $response = $this->postJson(route('shops.store'), $shopData);
        $response->assertStatus(201)
                 ->assertJsonPath('name', $shopData['name'])
                 ->assertJsonPath('owner.id', $this->shopOwnerA->id);

        $newShop = app(config('shop-manager.shop_model'))->find($response->json('id'));
        $this->assertTrue($this->shopOwnerA->hasShopRole('shop_admin', $newShop));
    }

    /** @test */
    public function shop_owner_can_update_their_own_shop()
    {
        $this->actingAs($this->shopOwnerA, 'api');
        $updatedData = ['description' => 'Updated description for Alice Wonderland Shop'];
        $response = $this->putJson(route('shops.update', ['shopSlugOrId' => $this->shopA->slug]), $updatedData);
        $response->assertStatus(200)
                 ->assertJsonPath('description', $updatedData['description']);
    }

    /** @test */
    public function non_owner_without_role_cannot_update_shop()
    {
        $this->actingAs($this->basicUser, 'api');
        $response = $this->putJson(route('shops.update', ['shopSlugOrId' => $this->shopA->slug]), ['description' => 'Attempt to update']);
        $response->assertStatus(403); // Or based on your authorization logic
    }

    /** @test */
    public function platform_admin_can_update_any_shop()
    {
        $this->actingAs($this->platformAdmin, 'api');
        $updatedData = ['name' => 'Platform Updated Shop Name'];
        $response = $this->putJson(route('shops.update', ['shopSlugOrId' => $this->shopA->slug]), $updatedData);
        $response->assertStatus(200)
                 ->assertJsonPath('name', $updatedData['name']);
    }

    /** @test */
    public function shop_owner_can_upload_logo_and_cover_to_their_shop()
    {
        $this->actingAs($this->shopOwnerA, 'api');
        Storage::fake(config('media-uploader.default_disk'));

        $logoFile = UploadedFile::fake()->image('logo.png');
        $coverFile = UploadedFile::fake()->image('cover.jpg');

        $response = $this->putJson(route('shops.update', ['shopSlugOrId' => $this->shopA->slug]), [
            'name' => $this->shopA->name, // Name is required for update validation if not 'sometimes'
            'logo' => $logoFile,
            'cover_image' => $coverFile,
        ]);
        $response->assertStatus(200);

        $this->shopA->refresh();
        $this->assertNotNull($this->shopA->getFirstMedia(config('shop-manager.media_collections.shop_logo.name', 'shop_logo')));
        $this->assertNotNull($this->shopA->getFirstMedia(config('shop-manager.media_collections.shop_cover_image.name', 'shop_cover_image')));

        $logoMedia = $this->shopA->getFirstMedia(config('shop-manager.media_collections.shop_logo.name', 'shop_logo'));
        Storage::disk($logoMedia->disk)->assertExists($logoMedia->path);
    }

    // --- Shop Member Management Tests ---
    /** @test */
    public function shop_admin_can_add_a_member_with_a_role_to_their_shop()
    {
        $this->actingAs($this->shopOwnerA, 'api'); // OwnerA is shop_admin for shopA

        $response = $this->postJson(route('shops.members.store', ['shopSlugOrId' => $this->shopA->slug]), [
            'user_id' => $this->shopEditorA->id,
            'role' => 'shop_editor',
        ]);
        $response->assertStatus(200); // Or 201 if you prefer for creation
        $this->assertTrue($this->shopEditorA->hasShopRole('shop_editor', $this->shopA));
    }

    /** @test */
    public function shop_admin_can_list_shop_members()
    {
        // First, add shopEditorA as a member
        $this->shopA->addMember($this->shopEditorA, 'shop_editor');

        $this->actingAs($this->shopOwnerA, 'api');
        $response = $this->getJson(route('shops.members.index', ['shopSlugOrId' => $this->shopA->slug]));
        $response->assertStatus(200)
                 ->assertJsonCount(2); // Owner (shop_admin) + shopEditorA (shop_editor)
                 // Note: getMembersWithShopRoles might list owner if owner is also explicitly given a role.
                 // The owner is automatically shop_admin due to ShopController::store logic.

        $responseData = $response->json();
        $foundEditor = false;
        foreach($responseData as $member) {
            if ($member['id'] === $this->shopEditorA->id && $member['shop_role_name'] === 'shop_editor') {
                $foundEditor = true;
                break;
            }
        }
        $this->assertTrue($foundEditor, "Shop editor not found in member list or role is incorrect.");
    }

    /** @test */
    public function shop_admin_can_update_a_member_role_in_their_shop()
    {
        $this->shopA->addMember($this->shopEditorA, 'shop_viewer'); // Add as viewer first
        $this->assertTrue($this->shopEditorA->hasShopRole('shop_viewer', $this->shopA));
        $this->assertFalse($this->shopEditorA->hasShopRole('shop_editor', $this->shopA));

        $this->actingAs($this->shopOwnerA, 'api');
        $response = $this->putJson(route('shops.members.updateRole', ['shopSlugOrId' => $this->shopA->slug, 'userId' => $this->shopEditorA->id]), [
            'role' => 'shop_editor',
        ]);
        $response->assertStatus(200);

        $this->shopEditorA->refresh(); // Refresh user model to get updated roles
        $this->assertTrue($this->shopEditorA->hasShopRole('shop_editor', $this->shopA));
        $this->assertFalse($this->shopEditorA->hasShopRole('shop_viewer', $this->shopA)); // Old role should be gone
    }

    /** @test */
    public function shop_admin_can_remove_a_member_from_their_shop()
    {
        $this->shopA->addMember($this->shopEditorA, 'shop_editor');
        $this->assertTrue($this->shopEditorA->hasShopRole('shop_editor', $this->shopA));

        $this->actingAs($this->shopOwnerA, 'api');
        $response = $this->deleteJson(route('shops.members.destroy', ['shopSlugOrId' => $this->shopA->slug, 'userId' => $this->shopEditorA->id]));
        $response->assertStatus(200);

        $this->shopEditorA->refresh();
        $this->assertFalse($this->shopEditorA->hasShopRole('shop_editor', $this->shopA));
    }

    /** @test */
    public function shop_owner_cannot_be_removed_from_shop_via_member_endpoint()
    {
        $this->actingAs($this->shopOwnerA, 'api');
        $response = $this->deleteJson(route('shops.members.destroy', ['shopSlugOrId' => $this->shopA->slug, 'userId' => $this->shopOwnerA->id]));
        $response->assertStatus(403); // Forbidden or specific error
    }

    // --- Shop Content (Posts) ---
    /** @test */
    public function shop_admin_can_create_a_post_for_the_shop()
    {
        $this->actingAs($this->shopOwnerA, 'api'); // shopOwnerA is shop_admin of shopA
        // We need a route for shop posts, e.g., POST /api/v1/shops/{shopSlugOrId}/posts
        // This route is not defined yet in ShopManager's api.php.
        // For now, let's test the model method directly.

        // Gate::define('createPostForShop', function (User $user, Shop $shop) {
        //     return $user->hasShopRole(['shop_admin', 'shop_editor'], $shop) || $user->id === $shop->owner_id;
        // });
        // $this->assertTrue(Gate::allows('createPostForShop', [$this->shopOwnerA, $this->shopA]));

        $postContent = "A new shiny product announcement from {$this->shopA->name}!";
        $post = $this->shopA->createPost(['content' => $postContent]);

        $this->assertNotNull($post);
        $this->assertEquals($this->shopA->id, $post->author_id);
        $this->assertEquals($this->shopA->getMorphClass(), $post->author_type);
        $this->assertEquals($postContent, $post->content);
    }

    // TODO: Tests for platform_admin managing any shop
    // TODO: Tests for non-authorized users trying to manage members/content
    // TODO: Tests for shop being searchable (requires scout:import for shops)
}
