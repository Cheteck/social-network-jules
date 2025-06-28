<?php

namespace Ijideals\SocialPosts\Providers;

use Illuminate\Support\ServiceProvider;

class SocialPostsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pour l'instant, pas de services spécifiques à enregistrer dans le conteneur.
        // Pourrait être utilisé pour lier des interfaces à des implémentations si besoin.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Charger les migrations du package
        // Le chemin pointe vers le dossier 'database/migrations' à la racine du package
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Charger les routes API du package
        // Le chemin pointe vers le fichier 'routes/api.php' à la racine du package
        // Nous utiliserons $this->routes() pour enregistrer les routes afin qu'elles soient groupées
        // sous le middleware 'api' et avec le bon préfixe si nécessaire.
        $this->loadRoutes();

        // Si nous avions des configurations à publier :
        // $this->publishes([
        //     __DIR__.'/../../config/social-posts.php' => config_path('social-posts.php'),
        // ], 'config');

        // Si nous avions des vues à publier/charger :
        // $this->loadViewsFrom(__DIR__.'/../../resources/views', 'social-posts');
        // $this->publishes([
        //     __DIR__.'/../../resources/views' => resource_path('views/vendor/social-posts'),
        // ], 'views');
    }

    /**
     * Helper method to load package routes.
     */
    protected function loadRoutes(): void
    {
        if (file_exists(__DIR__.'/../../routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        }

        // Si nous avions des routes web:
        // if (file_exists(__DIR__.'/../../routes/web.php')) {
        //     $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        // }
    }
}
