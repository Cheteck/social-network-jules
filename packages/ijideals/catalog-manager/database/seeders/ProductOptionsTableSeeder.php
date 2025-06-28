<?php

    namespace Ijideals\CatalogManager\Database\Seeders;

    use Illuminate\Database\Seeder;
    use Ijideals\CatalogManager\Models\ProductOption;
    use Ijideals\CatalogManager\Models\ProductOptionValue;

    class ProductOptionsTableSeeder extends Seeder
    {
        public function run(): void
        {
            // Option: Color
            $colorOption = ProductOption::firstOrCreate(
                ['name' => 'Color'],
                ['display_type' => 'color_swatch']
            );

            $colorOption->values()->firstOrCreate(['value' => 'Red', 'display_label' => '#FF0000', 'order_column' => 1]);
            $colorOption->values()->firstOrCreate(['value' => 'Green', 'display_label' => '#00FF00', 'order_column' => 2]);
            $colorOption->values()->firstOrCreate(['value' => 'Blue', 'display_label' => '#0000FF', 'order_column' => 3]);
            $colorOption->values()->firstOrCreate(['value' => 'Black', 'display_label' => '#000000', 'order_column' => 4]);
            $colorOption->values()->firstOrCreate(['value' => 'White', 'display_label' => '#FFFFFF', 'order_column' => 5]);

            // Option: Size
            $sizeOption = ProductOption::firstOrCreate(
                ['name' => 'Size'],
                ['display_type' => 'dropdown']
            );

            $sizeOption->values()->firstOrCreate(['value' => 'S', 'display_label' => 'Small', 'order_column' => 1]);
            $sizeOption->values()->firstOrCreate(['value' => 'M', 'display_label' => 'Medium', 'order_column' => 2]);
            $sizeOption->values()->firstOrCreate(['value' => 'L', 'display_label' => 'Large', 'order_column' => 3]);
            $sizeOption->values()->firstOrCreate(['value' => 'XL', 'display_label' => 'Extra Large', 'order_column' => 4]);

            // Option: Material (example)
            $materialOption = ProductOption::firstOrCreate(
                ['name' => 'Material'],
                ['display_type' => 'radio']
            );
            $materialOption->values()->firstOrCreate(['value' => 'Cotton', 'order_column' => 1]);
            $materialOption->values()->firstOrCreate(['value' => 'Polyester', 'order_column' => 2]);
            $materialOption->values()->firstOrCreate(['value' => 'Silk', 'order_column' => 3]);

            $this->command->info('Product options and values seeded successfully.');
        }
    }
