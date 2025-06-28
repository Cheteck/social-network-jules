<?php

namespace Ijideals\HashtagSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Ijideals\HashtagSystem\Models\Hashtag;

class HashtagSystemDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hashtags = [
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'TailwindCSS', 'slug' => 'tailwindcss'],
            ['name' => 'VueJS', 'slug' => 'vuejs'],
            ['name' => 'Livewire', 'slug' => 'livewire'],
            ['name' => 'Tutorial', 'slug' => 'tutorial'],
            ['name' => 'News', 'slug' => 'news'],
            ['name' => 'OpenSource', 'slug' => 'opensource'],
            ['name' => 'Package', 'slug' => 'package'],
            ['name' => 'Development', 'slug' => 'development'],
        ];

        foreach ($hashtags as $tag) {
            Hashtag::firstOrCreate(['slug' => $tag['slug']], ['name' => $tag['name']]);
        }

        // Vous pourriez aussi utiliser la factory pour en créer plus si désiré
        // Hashtag::factory()->count(10)->create();
    }
}
