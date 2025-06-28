<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile; // For media simulation
use Illuminate\Support\Facades\Storage; // For media simulation
use App\Models\User;
use Ijideals\ShopManager\Models\Shop;
use Ijideals\CatalogManager\Models\Category;
use Ijideals\CatalogManager\Models\ProductOption;
use Ijideals\CatalogManager\Models\ProductOptionValue;
use Ijideals\CatalogManager\Models\Product;
use Ijideals\CatalogManager\Models\ProductVariant;
use Ijideals\SocialPosts\Models\Post;
use Spatie\Permission\Models\Role;
use Ijideals\HashtagSystem\Database\Seeders\HashtagSystemDemoSeeder; // Ajout du seeder de hashtags


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Call package seeders first (roles, permissions, default product options)
        $this->call([
            \Ijideals\ShopManager\Database\Seeders\RolesAndPermissionsSeeder::class,
            \Ijideals\CatalogManager\Database\Seeders\ProductOptionsTableSeeder::class,
            HashtagSystemDemoSeeder::class, // Appel du seeder de hashtags
            // Add other package-specific seeders here if they are created
        ]);

        // --- Create Users ---
        $platformSuperAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
        ]);
        $platformSuperAdmin->assignRole('platform_superadmin');

        $platformAdmin = User::factory()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
        ]);
        $platformAdmin->assignRole('platform_admin');

        $shopOwner1 = User::factory()->create(['name' => 'Alice Owner', 'email' => 'alice@shop.com']);
        $shopOwner2 = User::factory()->create(['name' => 'Bob Owner', 'email' => 'bob@shop.com']);
        $editorUser = User::factory()->create(['name' => 'Charlie Editor', 'email' => 'charlie@editor.com']);
        $viewerUser = User::factory()->create(['name' => 'David Viewer', 'email' => 'david@viewer.com']);
        $normalUser1 = User::factory()->create(['name' => 'Eve Customer', 'email' => 'eve@customer.com']);
        $normalUser2 = User::factory()->create(['name' => 'Frank Customer', 'email' => 'frank@customer.com']);

        $usersForInteractions = collect([$shopOwner1, $shopOwner2, $editorUser, $viewerUser, $normalUser1, $normalUser2]);
        User::factory(10)->create()->each(function ($user) use ($usersForInteractions) {
            $usersForInteractions->push($user);
        });


        // --- Create Shops ---
        $shopA = Shop::factory()->ownedBy($shopOwner1)->create(['name' => 'Alice\'s Emporium']);
        $shopB = Shop::factory()->ownedBy($shopOwner2)->create(['name' => 'Bob\'s Bargains']);

        $shopOwnerRoleName = config('shop-manager.permission.default_shop_roles.0', 'shop_owner'); // Assuming 'shop_owner' is first
        $shopOwnerRole = Role::findByName($shopOwnerRoleName, $platformAdmin->guard_name); // Use any existing user's guard

        if ($shopOwnerRole) {
            // Manually attach role with team_id for clarity in seeder
            $shopOwner1->roles()->attach($shopOwnerRole->id, [config('permission.column_names.team_foreign_key') => $shopA->id]);
            $shopOwner1->forgetCachedPermissions();
            $shopOwner2->roles()->attach($shopOwnerRole->id, [config('permission.column_names.team_foreign_key') => $shopB->id]);
            $shopOwner2->forgetCachedPermissions();
        }

        // Add members to Shop A
        $shopEditorRoleName = config('shop-manager.permission.default_shop_roles.1', 'shop_editor'); // Assuming 'shop_editor' is second
        $shopEditorRole = Role::findByName($shopEditorRoleName, $platformAdmin->guard_name);
        if($shopEditorRole) $shopA->addMember($editorUser, $shopEditorRole->name);

        $shopViewerRoleName = config('shop-manager.permission.default_shop_roles.2', 'shop_viewer'); // Assuming 'shop_viewer' is third
        $shopViewerRole = Role::findByName($shopViewerRoleName, $platformAdmin->guard_name);
        if($shopViewerRole) $shopA->addMember($viewerUser, $shopViewerRole->name);


        // --- Categories (some already seeded by ProductOptionsTableSeeder) ---
        $electronics = Category::where('slug', 'electronics')->first() ?? Category::factory()->create(['name' => 'Electronics']);
        $books = Category::where('slug', 'books')->first() ?? Category::factory()->create(['name' => 'Books']);
        $clothing = Category::factory()->create(['name' => 'Clothing']);
        $homeGoods = Category::factory()->create(['name' => 'Home Goods']);

        // --- Product Options (Color, Size, Material already seeded) ---
        $colorOption = ProductOption::where('name', 'Color')->first();
        $sizeOption = ProductOption::where('name', 'Size')->first();

        // --- Products for Shops ---
        $productA1 = Product::factory()->forShop($shopA)->create([
            'name' => 'Awesome T-Shirt', 'price' => 29.99, 'sku' => 'ATS001', 'stock_quantity' => 50
        ]);
        $productA1->categories()->attach([$clothing->id]);
        if ($colorOption && $sizeOption) $productA1->syncProductOptions([$colorOption->id, $sizeOption->id]);

        $productA2 = Product::factory()->forShop($shopA)->create([
            'name' => 'Super Laptop', 'price' => 1299.99, 'sku' => 'SLP001', 'stock_quantity' => 10
        ]);
        $productA2->categories()->attach([$electronics->id]);

        $productB1 = Product::factory()->forShop($shopB)->create([
            'name' => 'Mystery Novel', 'price' => 15.50, 'sku' => 'NVL001', 'stock_quantity' => 75
        ]);
        $productB1->categories()->attach([$books->id]);

        // --- Product Variants (Example for Awesome T-Shirt) ---
        if ($productA1 && $colorOption && $sizeOption) {
            $red = $colorOption->values()->where('value', 'Red')->first();
            $blue = $colorOption->values()->where('value', 'Blue')->first();
            $small = $sizeOption->values()->where('value', 'S')->first();
            $medium = $sizeOption->values()->where('value', 'M')->first();

            if ($red && $small) {
                ProductVariant::factory()->forProduct($productA1)->withOptionValues([$red->id, $small->id])
                    ->create(['sku' => 'ATS001-RED-S', 'price_modifier' => 0, 'stock_quantity' => 10]);
            }
            if ($red && $medium) {
                ProductVariant::factory()->forProduct($productA1)->withOptionValues([$red->id, $medium->id])
                    ->create(['sku' => 'ATS001-RED-M', 'price_modifier' => 2, 'stock_quantity' => 15]);
            }
            if ($blue && $medium) {
                ProductVariant::factory()->forProduct($productA1)->withOptionValues([$blue->id, $medium->id])
                    ->create(['sku' => 'ATS001-BLUE-M', 'price_modifier' => 2.5, 'stock_quantity' => 5]);
            }
        }

        // --- Posts (by Users and Shops) ---
        Post::factory(15)->authoredByUser($normalUser1)->create()->each(function ($post) {
            $post->syncHashtags($post->content . ' #userPost #general');
        });
        Post::factory(10)->authoredByUser($normalUser2)->create()->each(function ($post) {
            $post->syncHashtags($post->content . ' #randomThoughts');
        });
        Post::factory(5)->authoredByShop($shopA)->create(['content' => 'Special promotion from Alice\'s Emporium! #promotion #sale #alicesEmporium'])->each(function ($post) {
            $post->syncHashtags($post->content); // Hashtags already in content
        });
        Post::factory(3)->authoredByShop($shopB)->create(['content' => 'New arrivals at Bob\'s Bargains! #new #bobsBargains'])->each(function ($post) {
            $post->syncHashtags($post->content); // Hashtags already in content
        });

        // --- Social Interactions ---
        // Re-fetch all posts as their content might have been implicitly changed by factories or above logic if not careful
        // However, syncHashtags doesn't change content, so this is fine.
        $allPosts = Post::all();

        foreach ($usersForInteractions as $user) {
            $otherUsers = $usersForInteractions->except($user->id);
            if ($otherUsers->isNotEmpty()) {
                $usersToFollow = $otherUsers->random(min(3, $otherUsers->count()));
                foreach ($usersToFollow as $userToFollow) {
                    $user->follow($userToFollow);
                }
            }

            if ($allPosts->isNotEmpty()) {
                foreach ($allPosts->random(min(10, $allPosts->count())) as $postToLike) {
                     if (!$postToLike->author || ($postToLike->author_id !== $user->id || $postToLike->author_type !== get_class($user))) {
                        $user->like($postToLike);
                    }
                }
                foreach ($allPosts->random(min(3, $allPosts->count())) as $postToComment) {
                     $comment = $postToComment->addComment($this->faker()->sentence, $user);
                     // Add replies to some comments
                     if ($comment && $this->faker()->boolean(30) && $otherUsers->isNotEmpty()) {
                         $postToComment->addComment($this->faker()->sentence, $otherUsers->random(), $comment);
                     }
                }
            }
        }

        // --- User Settings (Example) ---
        $normalUser1->setSetting('notifications.new_like.database', false);
        $normalUser2->setSetting('notifications.new_follower.database', true);
        if($usersForInteractions->has(2)) { // editorUser
            $usersForInteractions[2]->setSetting('privacy.profile_visibility', 'followers_only');
        }


        $this->command->info('Database seeded with sample data, including roles, permissions, users, shops, products with variants, posts, and interactions.');
    }
}
