<?php

namespace Ijideals\HashtagSystem\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class Hashtag
 * @package Ijideals\HashtagSystem\Models
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Hashtag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($hashtag) {
            if (empty($hashtag->slug)) {
                $hashtag->slug = Str::slug($hashtag->name);
            }
        });

        static::updating(function ($hashtag) {
            if ($hashtag->isDirty('name') && empty($hashtag->slug)) {
                $hashtag->slug = Str::slug($hashtag->name);
            }
        });
    }

    /**
     * Get all of the models that are assigned this hashtag.
     * Dynamically define relationships based on configured models.
     */
    public function __call($method, $parameters)
    {
        // This is a simple way to define relationships for known hashtagged models.
        // A more robust solution might involve configuration or a discovery mechanism.
        if (Str::endsWith($method, 's') && !method_exists($this, $method)) { // Changed ends_with to endsWith
                $modelNameSingular = Str::singular($method);
                $modelNameStudly = Str::studly($modelNameSingular);

                // Define a map of expected model locations or use a configuration.
                // For testing, we can make it more flexible.
                $possibleModelClasses = [
                    "App\\Models\\{$modelNameStudly}",
                    "Ijideals\\SocialPosts\\Models\\{$modelNameStudly}", // Common package
                    "Ijideals\\HashtagSystem\\Tests\\TestSupport\\Models\\{$modelNameStudly}", // For internal testing
                ];

                // Allow specific override for 'posts' relation during testing to point to TestPost
                if ($method === 'posts' && class_exists("Ijideals\\HashtagSystem\\Tests\\TestSupport\\Models\\TestPost")) {
                    // If we are in a test environment and 'TestPost' exists, 'posts' relation points to it.
                    // This helps in testing API endpoints like /hashtags/{slug}/posts
                     if (app()->environment('testing')) {
                        return $this->morphedByMany("Ijideals\\HashtagSystem\\Tests\\TestSupport\\Models\\TestPost", 'hashtaggable', 'hashtaggables', 'hashtag_id', 'hashtaggable_id');
                     }
                }


                foreach ($possibleModelClasses as $modelClass) {
                    if (class_exists($modelClass)) {
                        return $this->morphedByMany($modelClass, 'hashtaggable', 'hashtaggables', 'hashtag_id', 'hashtaggable_id');
                    }
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        // If you have a specific factory for this package, specify it here.
        // e.g., return \Ijideals\HashtagSystem\Database\Factories\HashtagFactory::new();
        // For now, relies on Laravel's default factory logic or one defined in the main app.
        // It's better to define it explicitly if the package has its own factory.
        // As a placeholder:
        $factoryClassName = 'Ijideals\\HashtagSystem\\Database\\Factories\\HashtagFactory';
        if (class_exists($factoryClassName)) {
            return $factoryClassName::new();
        }
        // Fallback for projects that might define it in the default location
        $appFactoryClassName = 'Database\\Factories\\Ijideals\\HashtagSystem\\Models\\HashtagFactory';
         if (class_exists($appFactoryClassName)) {
            return $appFactoryClassName::new();
        }
        // If no specific factory, let Laravel try to resolve.
        return \Illuminate\Database\Eloquent\Factories\Factory::factoryForModel(get_called_class());
    }
}
