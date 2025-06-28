<?php

    namespace Ijideals\CatalogManager\Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Ijideals\CatalogManager\Models\Category;

    class CategoryFactory extends Factory
    {
        protected $model = Category::class;

        public function definition(): array
        {
            return [
                'name' => $this->faker->unique()->words(2, true), // Unique to avoid too many slug collisions initially
                'description' => $this->faker->optional()->sentence,
                'parent_id' => null, // Default to top-level category
            ];
        }

        /**
         * Indicate that the category has a specific parent.
         *
         * @param \Ijideals\CatalogManager\Models\Category|int $parent
         * @return \Illuminate\Database\Eloquent\Factories\Factory
         */
        public function withParent($parent)
        {
            return $this->state(function (array $attributes) use ($parent) {
                return [
                    'parent_id' => $parent instanceof Category ? $parent->id : $parent,
                ];
            });
        }
    }
