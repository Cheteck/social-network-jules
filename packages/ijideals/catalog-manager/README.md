# Laravel Catalog Manager (ijideals/catalog-manager)

This package provides functionality for managing a product catalog, allowing shops (from `ijideals/shop-manager`) to list and manage their products, organized by global categories. Products can have images (via `ijideals/media-uploader`) and will be searchable (via `ijideals/search-engine`).

## Features

-   **Product Management:**
    -   CRUD operations for products (name, description, price, SKU, stock, images).
    -   Products are associated with a specific `Shop`.
    -   Products can be assigned to one or more `Categories`.
    -   Stock quantity management (configurable).
    -   Active/inactive and featured status for products.
    -   Slug generation for products (unique per shop).
    -   Integration with `ijideals/media-uploader` for multiple product images.
    -   Products are made searchable via `Laravel\Scout` integration.
-   **Product Options & Variants:**
    -   Define global product options (e.g., "Size", "Color") and their possible values.
    -   Associate options with base products.
    -   Generate and manage product variants (unique combinations of option values) with their own SKU, price/price_modifier, stock, and images.
-   **Category Management:**
    -   CRUD operations for global product categories.
    *   Support for hierarchical categories (parent/child).
    -   Slug generation for categories.
-   **Permissions:**
    -   Product, Option, and Variant management is restricted to authorized users (platform admins for global options, shop admins/editors for products/variants).
-   **API Endpoints:** For managing categories, product options, products, and product variants.

## Dependencies

-   `ijideals/shop-manager`: For associating products with shops and for shop-level permissions.
-   `ijideals/media-uploader`: For handling product image uploads.
-   `laravel/scout`: For making products searchable (if search integration is enabled).
-   `spatie/laravel-permission`: Used by `ijideals/shop-manager` for roles and permissions.

## Installation

1.  **Require Dependencies:**
    Ensure `ijideals/shop-manager` and `ijideals/media-uploader` are already installed and configured. If you plan to use search, ensure `laravel/scout` and `ijideals/search-engine` are also set up.

2.  **Require this package:**
    ```bash
    composer require ijideals/catalog-manager
    ```
    (If local, ensure path repository is set in main `composer.json` for all `ijideals/*` packages).

3.  **Service Provider:**
    Laravel's auto-discovery should detect `Ijideals\CatalogManager\Providers\CatalogManagerServiceProvider`. If not, add it to `config/app.php`.

4.  **Publish Configuration & Migrations:**
    ```bash
    php artisan vendor:publish --provider="Ijideals\CatalogManager\Providers\CatalogManagerServiceProvider" --tag="catalog-manager-config"
    php artisan vendor:publish --provider="Ijideals\CatalogManager\Providers\CatalogManagerServiceProvider" --tag="catalog-manager-migrations"
    ```
    This publishes:
    *   `config/catalog-manager.php`: Configure models, table names, media collections, route prefixes, etc.
    *   Migrations for `products`, `categories`, `category_product`, `product_options`, `product_option_values`, `product_variants`, `product_product_option`, and `product_variant_option_values` tables.

5.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```

## Configuration (`config/catalog-manager.php`)

Review the published configuration file and adjust as needed:
*   `user_model`, `shop_model`, `product_model`, `category_model`, `product_option_model`, `product_option_value_model`, `product_variant_model`: Ensure these point to your correct models.
*   `tables`: Customize table names for all catalog-related entities.
*   `media_collections`: Define settings for product images and product variant images.
*   `*_slug_source_field`: Field used to generate slugs for products and categories.
*   `route_prefixes`: Customize API route prefixes.
*   `pagination_items`: Default items per page for products and categories.
*   `stock_management_enabled`: Enable/disable stock tracking for products.

## Usage

### 1. Models & Relationships

*   **`Ijideals\CatalogManager\Models\Product`**:
    *   Uses `HasMedia` (for base product images), `Searchable`.
    *   Relations: `shop()`, `categories()`, `productOptions()` (options this product uses), `variants()`.
    *   Methods: `attachProductOption()`, `detachProductOption()`, `syncProductOptions()`, `getAllowedValuesForOption()`.
*   **`Ijideals\CatalogManager\Models\Category`**:
    *   Hierarchical: `parent()`, `children()`, `descendants()`.
    *   Relation: `products()`.
*   **`Ijideals\CatalogManager\Models\ProductOption`**: (Global by default)
    *   Relations: `values()` (to `ProductOptionValue`), `products()` (products using this option type).
*   **`Ijideals\CatalogManager\Models\ProductOptionValue`**:
    *   Relations: `option()` (to `ProductOption`), `variants()` (variants defined by this value).
*   **`Ijideals\CatalogManager\Models\ProductVariant`**:
    *   Uses `HasMedia` (for variant-specific images, collection e.g., `product_variant_images`).
    *   Relations: `product()` (to base product), `optionValues()` (the specific combination defining this variant).
    *   Accessors: `calculated_price`, `display_name`.
*   **`Ijideals\ShopManager\Models\Shop`**:
    *   Has many `Product` (`$shop->products()`).

### 2. Managing Product Options & Values (Platform Admin)

Global product options (e.g., "Color", "Size") and their possible values (e.g., "Red", "Small") are managed via:
Prefix: `config('catalog-manager.route_prefixes.product_options')` (e.g., `/api/v1/catalog/product-options`)
*   `GET /`: List all product options (with their values).
*   `POST /`: Create a new product option.
*   `GET /{optionId}`: Show a specific product option.
*   `PUT|PATCH /{optionId}`: Update a product option.
*   `DELETE /{optionId}`: Delete a product option.
*   `GET /{optionId}/values`: List values for a specific option.
*   `POST /{optionId}/values`: Add a new value to an option.
*   `PUT|PATCH /{optionId}/values/{valueId}`: Update an option value.
*   `DELETE /{optionId}/values/{valueId}`: Delete an option value.

### 3. Managing Products (Shop Admin/Editor)

Prefix: `config('catalog-manager.route_prefixes.shop_products')` (e.g., `/api/v1/shops/{shopSlugOrId}/products`)

*   **CRUD for Products:**
    *   `GET /`: List products for the shop.
    *   `POST /`: Create a new base product (can include `category_ids[]`, `images[]`).
    *   `GET /{productSlugOrId}`: Show a specific product.
    *   `PUT|PATCH /{productSlugOrId}`: Update a product.
    *   `DELETE /{productSlugOrId}`: Delete a product.
*   **Associating Options with a Product:** (Endpoints under `/{productSlugOrId}/options`)
    *   `GET /options`: List options currently associated with the product.
    *   `POST /options`: Attach a global `ProductOption` to this product (expects `product_option_id`).
    *   `DELETE /options/{optionId}`: Detach a `ProductOption` from this product.
    *   `PUT /options`: Sync all `ProductOption` associations for this product (expects `option_ids[]`).
*   **Managing Product Variants:** (Endpoints under `/{productSlugOrId}/variants`)
    *   `GET /variants`: List active variants for the product.
    *   `POST /variants`: Create a new, specific product variant (expects `sku`, `price`/`price_modifier`, `stock_quantity`, `option_value_ids[]`, `images[]`).
    *   `POST /variants/generate`: Generate all possible variants based on the product's associated options and their values. Accepts defaults for new variants.
    *   `GET /variants/{variantId}`: Show a specific variant.
    *   `PUT|PATCH /variants/{variantId}`: Update a variant.
    *   `DELETE /variants/{variantId}`: Delete a variant.


### 4. Making Products & Variants Searchable

If you want products to be searchable via `ijideals/search-engine`:
1.  Ensure the `Product` model uses the `Laravel\Scout\Searchable` trait (already included).
2.  Implement `toSearchableArray()` and `searchableAs()` in the `Product` model (already included).
3.  Add the `Product` model to the `searchable_models` array in `config/search-engine.php`:
    ```php
    // config/search-engine.php
    'searchable_models' => [
        // ... other models
        'product' => \Ijideals\CatalogManager\Models\Product::class,
    ],
    ```
4.  Import products into the Scout index:
    ```bash
    php artisan scout:import "Ijideals\CatalogManager\Models\Product"
    ```

### 3. API Endpoints

**Categories (Global - Platform Admin Access Recommended for CUD operations):**
Prefix: `config('catalog-manager.route_prefixes.categories')` (e.g., `/api/v1/catalog/categories`)
*   `GET /`: List categories (supports `?top_level_only=1`, `?with_children=1`).
*   `POST /`: Create a new category (Auth + Platform Admin permission).
*   `GET /{categorySlugOrId}`: Show a specific category.
*   `PUT|PATCH /{categorySlugOrId}`: Update a category (Auth + Platform Admin permission).
*   `DELETE /{categorySlugOrId}`: Delete a category (Auth + Platform Admin permission).

**Products (Shop-Specific - Shop Admin/Editor Permissions for CUD operations):**
Prefix: `config('catalog-manager.route_prefixes.shop_products')` (e.g., `/api/v1/shops/{shopSlugOrId}/products`)
*   `GET /`: List active products for the specified shop.
    *   Optional query param: `?category={categorySlugOrId}`
*   `POST /`: Create a new product for the shop (Auth + Shop Permission).
    *   Accepts `name`, `description`, `price`, `sku`, `stock_quantity`, `is_active`, `category_ids[]`, `images[]`.
*   `GET /{productSlugOrId}`: Show a specific product from the shop.
*   `PUT|PATCH /{productSlugOrId}`: Update a product (Auth + Shop Permission).
*   `DELETE /{productSlugOrId}`: Delete a product (Auth + Shop Permission).


### 4. Permissions

-   **Category Management:** It's recommended to protect CUD operations on categories using a global permission like `manage_catalog_categories` assigned to platform administrators. This package does not enforce this directly in the controller's middleware for MVP but expects the application to handle it.
-   **Product Management:** The `ProductController` includes placeholders for authorization checks (e.g., `Auth::user()->cannot('createProductInShop', $shop)`). You should define appropriate policies (e.g., `ProductPolicy`, `ShopPolicy`) or use the `hasShopRole` checks to ensure only authorized shop members (like `shop_admin` or `shop_editor`) can manage products for their specific shop.

### 5. Localization

API response messages (especially errors) can be translated. This package is structured to support language files.
*   **Publishing Language Files (if/when added):**
    ```bash
    php artisan vendor:publish --provider="Ijideals\CatalogManager\Providers\CatalogManagerServiceProvider" --tag="catalog-manager-lang"
    ```

## Testing

```bash
# From your Laravel application root
./vendor/bin/phpunit packages/ijideals/catalog-manager/tests
```

## Future Considerations
-   Product Variants (size, color, etc.).
-   Advanced stock management (tracking history, low stock alerts).
-   Pricing tiers or sale prices.
-   Product reviews and ratings.
-   More sophisticated category filtering and display options.
```
