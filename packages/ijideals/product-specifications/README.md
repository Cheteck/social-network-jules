# IJIDeals Product Specifications

A Laravel package to manage non-variant-generating product specifications (e.g., Material, Weight, Dimensions) for products managed by `ijideals/catalog-manager`.

## Features

-   Defines `SpecificationKey`s (e.g., "Material", "Weight") globally, including their type (string, number) and unit (cm, kg).
-   Allows associating these keys with specific values for each product (e.g., Product A's Material is "Cotton").
-   Integrates with the `Product` model from `ijideals/catalog-manager` to provide:
    -   A `specificationValues()` relationship.
    -   A `syncSpecifications()` method to easily manage a product's specifications.
    -   A `specifications` accessor to get a simple key-value map of a product's specs.
-   Provides API endpoints for administrators to manage `SpecificationKey`s.
-   Product specifications are included in the `ProductController` (from `catalog-manager`) responses and can be managed during product creation/update.

## Installation

1.  **Require the package and its dependencies via Composer:**
    ```bash
    composer require ijideals/product-specifications:@dev
    ```
    Ensure `ijideals/catalog-manager` is also required in your main project, as this package depends on it. This package's `composer.json` lists `ijideals/catalog-manager: @dev` as a requirement.

    If using local path repositories, ensure your main `composer.json` is configured correctly:
    ```json
    "repositories": [
        // ... other repositories
        {
            "type": "path",
            "url": "packages/ijideals/catalog-manager"
        },
        {
            "type": "path",
            "url": "packages/ijideals/product-specifications"
        }
        // ...
    ]
    ```

2.  **Run Migrations:**
    The package's service provider automatically loads its migrations. Run Laravel's migration command:
    ```bash
    php artisan migrate
    ```
    This will create `specification_keys` and `product_specification_values` tables.

## Usage

### 1. Managing Specification Keys (Admin)

API endpoints are provided for managing the global list of specification keys. These should be protected by admin authentication/authorization middleware.

-   **`GET /api/v1/admin/specification-keys`**: List all specification keys.
-   **`POST /api/v1/admin/specification-keys`**: Create a new specification key.
    -   Payload: `{ "name": "Key Name", "type": "string", "unit": "optional_unit" }`
-   **`GET /api/v1/admin/specification-keys/{keyId}`**: Show a specific key.
-   **`PUT /api/v1/admin/specification-keys/{keyId}`**: Update a key.
-   **`DELETE /api/v1/admin/specification-keys/{keyId}`**: Delete a key.

### 2. Managing Specifications for a Product

This is done via the `ProductController` in the `ijideals/catalog-manager` package when creating or updating a product.

The `Product` model (from `ijideals/catalog-manager`) now has a `syncSpecifications(array $specificationsData)` method and a `specifications` accessor.

**Example: Creating/Updating a Product with Specifications**

Send a `POST` or `PUT` request to the relevant product endpoint in `catalog-manager` (e.g., `/api/v1/shops/{shopId}/products` or `/api/v1/shops/{shopId}/products/{productId}`). Include a `specifications` array in your JSON payload:

```json
{
    "name": "Awesome T-Shirt",
    "price": 29.99,
    // ... other product fields ...
    "specifications": [
        {
            "key_id": 1, // ID of an existing SpecificationKey (e.g., for "Material")
            "value": "Organic Cotton"
        },
        {
            "key_name": "Weight", // Name of a SpecificationKey (will be created if it doesn't exist)
            "value": "200",
            "key_type": "number", // Optional: type for new key, defaults to 'string'
            "key_unit": "g"       // Optional: unit for new key
        },
        {
            "key_name": "Fit",
            "value": "Regular"
        }
    ]
}
```

-   If `key_id` is provided, it uses an existing `SpecificationKey`.
-   If `key_name` is provided, it finds or creates the `SpecificationKey`. `key_type` and `key_unit` can be provided for new keys.
-   If a specification's `value` is empty or null, that specification will be removed from the product.
-   If the top-level `specifications` array is empty or omitted, existing specifications for the product remain unchanged. To delete all specifications, pass an empty array: `"specifications": []`.

### 3. Accessing Product Specifications in Code

On a `Product` model instance from `ijideals/catalog-manager`:

```php
$product = Product::find(1);

// Get related ProductSpecificationValue models (with their SpecificationKey loaded)
$detailedSpecifications = $product->specificationValues()->with('specificationKey')->get();
foreach ($detailedSpecifications as $specValue) {
    echo $specValue->specificationKey->name . ': ' . $specValue->value . ($specValue->specificationKey->unit ?: '') . "\n";
}

// Get as a simple key-value array using the accessor
// (e.g., ['Material' => 'Cotton', 'Weight' => '200g'])
$keyValueSpecs = $product->specifications;
print_r($keyValueSpecs);
```

The `specifications` accessor will automatically append the unit to the value if a unit is defined for the `SpecificationKey`.

## Models

-   `IJIDeals\ProductSpecifications\Models\SpecificationKey`: Defines a specification type (name, type, unit).
-   `IJIDeals\ProductSpecifications\Models\ProductSpecificationValue`: Stores the value of a specific key for a specific product.

## Dependencies
-   `ijideals/catalog-manager`: This package enhances the Product model from `catalog-manager`.

## Testing
The package includes feature tests. Ensure you have set up your testing database and run migrations for this package and its dependencies (`catalog-manager`, `shop-manager`).

## Future Considerations
-   More granular control over `SpecificationKey` types and validation of `ProductSpecificationValue` based on the key's type.
-   Allowing predefined choices for `SpecificationKey`s of type 'select'.
-   Localization of `SpecificationKey` names and `ProductSpecificationValue` values.
