# User Profile Package (ijideals/user-profile)

Ce package Laravel fournit les fonctionnalités de base pour gérer les profils utilisateurs étendus.

## Fonctionnalités

-   Modèle `UserProfile` pour stocker les informations de profil (bio, site web, localisation, date de naissance).
-   Trait `HasProfile` à utiliser sur le modèle `User` pour une relation `hasOne` facile d'accès et la création automatique du profil si inexistant.
-   Endpoints API pour :
    -   Voir le profil d'un utilisateur : `GET /users/{user}/profile`
    -   Voir le profil de l'utilisateur authentifié : `GET /profile`
    -   Mettre à jour le profil de l'utilisateur authentifié : `PUT /profile`
-   Validation des données pour la mise à jour du profil via Form Request.

## Installation

1.  **Ajouter le package à votre `composer.json` principal :**
    ```json
    "require": {
        "ijideals/user-profile": "@dev"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../packages/ijideals/user-profile"
        }
    ]
    ```
    (Assurez-vous que le chemin `url` correspond à l'emplacement de votre package par rapport à votre application Laravel principale.)

2.  **Lancer `composer update`** dans votre application Laravel principale :
    ```bash
    composer update ijideals/user-profile
    ```

3.  **Publier les migrations (optionnel, mais recommandé) :**
    Le Service Provider charge les migrations automatiquement. Si vous souhaitez les publier pour les personnaliser :
    ```bash
    php artisan vendor:publish --provider="Ijideals\UserProfile\Providers\UserProfileServiceProvider" --tag="user-profile-migrations"
    ```

4.  **Exécuter les migrations :**
    ```bash
    php artisan migrate
    ```

## Utilisation

1.  **Ajouter le Trait `HasProfile` à votre modèle `User` :**
    ```php
    <?php

    namespace App\Models;

    // ... autres use statements ...
    use Ijideals\UserProfile\Concerns\HasProfile;

    class User extends Authenticatable
    {
        use // ... autres traits ...
            HasProfile;

        // ...
    }
    ```

2.  **Accéder au profil d'un utilisateur :**
    ```php
    $user = User::find(1);
    $profile = $user->profile; // Accède ou crée le profil

    echo $profile->bio;
    ```

3.  **Mettre à jour le profil (via l'API ou directement) :**
    Via l'API, l'utilisateur authentifié peut envoyer une requête PUT à `/profile` avec les données :
    ```json
    {
        "bio": "Ma nouvelle biographie.",
        "website": "https://mon-site.com",
        "location": "Ma ville",
        "birth_date": "1990-01-01"
    }
    ```
    Directement dans le code (par exemple, dans un seeder ou une commande) :
    ```php
    $user = User::find(1);
    $user->profile->update([
        'bio' => 'Nouvelle biographie depuis le code.',
        'website' => 'https://example-code.com'
    ]);
    // ou
    // $user->profile->bio = 'Bio modifiée';
    // $user->profile->save();
    ```

## Endpoints API

-   `GET /api/users/{user}/profile` : Voir le profil d'un utilisateur.
-   `GET /api/profile` : (Authentification requise) Voir son propre profil.
-   `PUT /api/profile` : (Authentification requise) Mettre à jour son propre profil.

(Préfixe `/api` supposé, cela dépend de la configuration de votre application principale pour le chargement des routes du package).

## Champs du Profil

-   `bio` (TEXT, nullable)
-   `website` (STRING, nullable, URL validée)
-   `location` (STRING, nullable)
-   `birth_date` (DATE, nullable, format YYYY-MM-DD, pas dans le futur)

La gestion des images (avatar, photo de couverture) n'est pas incluse dans cette version initiale et pourra être ajoutée ultérieurement.
```
