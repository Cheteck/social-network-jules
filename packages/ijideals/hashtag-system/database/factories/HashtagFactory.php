<?php

namespace Ijideals\HashtagSystem\Database\Factories;

use Ijideals\HashtagSystem\Models\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HashtagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Ijideals\HashtagSystem\Models\Hashtag>
     */
    protected $model = Hashtag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->unique()->word;
        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
