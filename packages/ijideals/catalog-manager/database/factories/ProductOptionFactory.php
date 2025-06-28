<?php

    namespace Ijideals\CatalogManager\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\CatalogManager\Models\ProductOption;

    class ProductOptionFactory extends Factory
    {
        protected $model = ProductOption::class;

        public function definition(): array
        {
            return [
                'name' => $this->faker->unique()->word, // e.g., Size, Color, Material
                'display_type' => $this->faker->randomElement(['dropdown', 'radio', 'color_swatch']),
            ];
        }
    }
