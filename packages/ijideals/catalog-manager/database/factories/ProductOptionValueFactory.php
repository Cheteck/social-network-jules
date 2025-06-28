<?php

    namespace Ijideals\CatalogManager\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\CatalogManager\Models\ProductOption;
    use Ijideals\CatalogManager\Models\ProductOptionValue;

    class ProductOptionValueFactory extends Factory
    {
        protected $model = ProductOptionValue::class;

        public function definition(): array
        {
            return [
                'product_option_id' => ProductOption::factory(),
                'value' => $this->faker->unique()->word, // e.g., Small, Red
                'display_label' => $this->faker->optional()->word, // e.g., S, #FF0000
                'order_column' => 0,
            ];
        }

        /**
         * Define a state for a specific option type (e.g., color).
         *
         * @param ProductOption $option
         * @param string $value
         * @param string|null $label
         * @return \Illuminate\Database\Eloquent\Factories\Factory
         */
        public function forOption(ProductOption $option, string $value, ?string $label = null)
        {
            return $this->state(function (array $attributes) use ($option, $value, $label) {
                return [
                    'product_option_id' => $option->id,
                    'value' => $value,
                    'display_label' => $label ?? $value,
                ];
            });
        }
    }
