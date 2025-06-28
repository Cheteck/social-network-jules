# Documentation de l'API Backend Laravel

Cette documentation décrit les principaux endpoints API du backend Laravel, y compris ceux fournis par les packages `ijideals/`.

## Authentification

### 1. Obtenir le Cookie CSRF (Sanctum)
- **Endpoint:** `/sanctum/csrf-cookie`
- **Méthode:** `GET`
- **Description:** Nécessaire avant les appels d'authentification pour initialiser la protection CSRF. Le backend retournera un cookie `XSRF-TOKEN`.
- **Réponse Succès:** `204 No Content` (avec le cookie XSRF-TOKEN défini).

### 2. Connexion Utilisateur (Fortify)
- **Endpoint:** `/login`
- **Méthode:** `POST`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "email": "user@example.com",
    "password": "password123",
    "remember": false // Optionnel
  }
  ```
- **Réponse Succès:** `200 OK` (Session établie) ou `204 No Content`.
- **Réponse Erreur (Validation):** `422 Unprocessable Entity` avec les erreurs de validation.
- **Réponse Erreur (Identifiants):** `422 Unprocessable Entity` ou message d'erreur spécifique.

### 3. Inscription Utilisateur (Fortify)
- **Endpoint:** `/register`
- **Méthode:** `POST`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "name": "Test User",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```
- **Réponse Succès:** `201 Created` ou `200 OK` (Session établie).
- **Réponse Erreur (Validation):** `422 Unprocessable Entity`.

### 4. Déconnexion Utilisateur (Fortify)
- **Endpoint:** `/logout`
- **Méthode:** `POST`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Réponse Succès:** `204 No Content` ou `200 OK`.

### 5. Obtenir l'Utilisateur Authentifié (Sanctum)
- **Endpoint:** `/api/user`
- **Méthode:** `GET`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`.
- **Réponse Succès (`200 OK`):**
  ```json
  {
    "id": 1,
    "name": "Test User",
    "email": "user@example.com",
    // ... autres champs du modèle User ...
  }
  ```
- **Réponse Erreur:** `401 Unauthorized` si non authentifié.

## Posts (Publications) - `ijideals/social-posts`
Préfixe: `/api/v1/social`
Middleware: `auth:sanctum` pour la plupart des routes.

### 1. Lister les Posts
- **Endpoint:** `/posts`
- **Méthode:** `GET`
- **Paramètres de Requête (Optionnel):**
    - `page={numero_page}` (pour la pagination, ex: `/posts?page=2`)
    - `author_id={user_id}` (pour filtrer les posts par auteur)
- **Réponse Succès (`200 OK`):** Structure paginée de Laravel.
  ```json
  {
    "data": [
      {
        "id": 1,
        "content": "Contenu du post...",
        "author": { /* ... objet auteur ... */ },
        "created_at": "...",
        // ... autres champs ...
      }
    ],
    "links": { /* ... liens de pagination ... */ },
    "meta": { /* ... méta-données de pagination ... */ }
  }
  ```

### 2. Créer un Post
- **Endpoint:** `/posts`
- **Méthode:** `POST`
- **Corps de la Requête (JSON):**
  ```json
  {
    "content": "Nouveau contenu de post."
    // "hashtags": ["tag1", "tag2"] // Optionnel, si la synchronisation des hashtags est active
  }
  ```
- **Réponse Succès (`201 Created`):** L'objet Post créé (avec l'auteur).
  ```json
  {
    "id": 2,
    "content": "Nouveau contenu de post.",
    "author": { /* ... */ },
    "created_at": "...",
    // ...
  }
  ```

### 3. Afficher un Post Spécifique
- **Endpoint:** `/posts/{post_id}`
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):** L'objet Post (avec l'auteur).

### 4. Mettre à Jour un Post
- **Endpoint:** `/posts/{post_id}`
- **Méthode:** `PUT` ou `PATCH`
- **Corps de la Requête (JSON):**
  ```json
  {
    "content": "Contenu mis à jour."
  }
  ```
- **Réponse Succès (`200 OK`):** L'objet Post mis à jour.

### 5. Supprimer un Post
- **Endpoint:** `/posts/{post_id}`
- **Méthode:** `DELETE`
- **Réponse Succès:** `204 No Content`.

## Fil d'Actualité (Feed) - `ijideals/news-feed-generator`
Préfixe: `/api/v1/feed` (configurable)
Middleware: `auth:api` (probablement `auth:sanctum`)

### 1. Obtenir le Fil d'Actualité
- **Endpoint:** `/` (relatif au préfixe, donc `/api/v1/feed`)
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):** Tableau de posts, potentiellement paginé. La structure exacte dépend de l'implémentation du service.
  ```json
  // Exemple de structure possible (basée sur LengthAwarePaginator)
  {
    "data": [
      { /* ... objet Post ... */ },
      { /* ... objet Post ... */ }
    ],
    "links": {
        "first": "url_to_first_page",
        "last": "url_to_last_page",
        "prev": null, // ou url_to_prev_page
        "next": "url_to_next_page" // ou null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5, // exemple
        "path": "base_url/api/v1/feed",
        "per_page": 15, // exemple
        "to": 15,
        "total": 70 // exemple
    }
  }
  ```

## Profils Utilisateurs - `ijideals/user-profile`
Préfixe: `/api`

### 1. Afficher un Profil Utilisateur (Public)
- **Endpoint:** `/users/{user_id}/profile` (où `{user_id}` est l'ID numérique de l'utilisateur)
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):** L'objet UserProfile.
  ```json
  {
    "id": 1, // User ID, pas UserProfile ID direct. UserProfile est lié via user_id.
    "name": "Nom Utilisateur", // Champ du modèle User
    "username": "username123", // Champ du modèle User ou UserProfile
    "avatar_url": "url_de_l_avatar.jpg", // Probablement un accesseur sur le modèle User
    "bio": "Biographie...", // Champ de UserProfile
    "website": "http://example.com", // Champ de UserProfile
    "location": "Ville, Pays", // Champ de UserProfile
    "birth_date": "YYYY-MM-DD", // Ou null, champ de UserProfile
    "followers_count": 150, // Calculé, probablement sur le modèle User
    "following_count": 75, // Calculé, probablement sur le modèle User
    "posts_count": 20, // Calculé, probablement sur le modèle User
    "created_at": "..." // Date d'inscription de l'User
  }
  ```

### 2. Afficher le Profil de l'Utilisateur Authentifié
- **Endpoint:** `/profile`
- **Méthode:** `GET`
- **Middleware:** `auth:sanctum`
- **Réponse Succès (`200 OK`):** L'objet UserProfile de l'utilisateur connecté.

### 3. Mettre à Jour le Profil de l'Utilisateur Authentifié
- **Endpoint:** `/profile`
- **Méthode:** `PUT`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "bio": "Nouvelle biographie...", // nullable, string, max:1000
    "website": "https://new-website.com", // nullable, url, max:255
    "location": "Nouvelle Ville", // nullable, string, max:255
    "birth_date": "YYYY-MM-DD" // nullable, date_format:Y-m-d, not in future
  }
  ```
- **Réponse Succès (`200 OK`):** L'objet UserProfile mis à jour.
- **Réponse Erreur (Validation):** `422 Unprocessable Entity` avec les erreurs de validation.

## Likes - `ijideals/likeable`
Préfixe: `/api/v1/likeable` (configurable via `likeable.route_prefix`)
Middleware: `auth:api` (probablement `auth:sanctum`)

### 1. Liker un Objet (ex: Post)
- **Endpoint:** `/{likeable_type}/{likeable_id}/like`
  - Exemple pour un post: `/posts/123/like` (si `likeable_type` est 'posts' et le préfixe est ajusté ou si les routes spécifiques posts sont utilisées)
  - Ou avec préfixe par défaut: `/api/v1/likeable/posts/123/like`
- **Méthode:** `POST`
- **Paramètres URL:**
    - `likeable_type`: String (ex: "posts", "comments"). Doit être mappé dans `config/likeable.php` ou le morphMap global.
    - `likeable_id`: Integer (ID de l'objet à liker).
- **Réponse Succès (`201 Created`):**
  ```json
  {
    "message": "Successfully liked.",
    "like": { /* ... objet Like ... */ },
    "likes_count": 26
  }
  ```
- **Réponse Erreur (`409 Conflict`):** Si déjà liké.
- **Réponse Erreur (`404 Not Found`):** Si l'objet à liker n'est pas trouvé.

### 2. Ne plus Liker un Objet (Unlike)
- **Endpoint:** `/{likeable_type}/{likeable_id}/unlike`
  - Exemple pour un post: `/api/v1/likeable/posts/123/unlike`
- **Méthode:** `DELETE`
- **Réponse Succès (`200 OK`):**
  ```json
  {
    "message": "Successfully unliked.",
    "likes_count": 25
  }
  ```
- **Réponse Erreur (`409 Conflict`):** Si pas encore liké.

### 3. Basculer l'état de Like d'un Objet (Toggle)
- **Endpoint:** `/{likeable_type}/{likeable_id}/toggle`
  - Exemple pour un post: `/api/v1/likeable/posts/123/toggle`
- **Méthode:** `POST`
- **Réponse Succès (`200 OK`):**
  ```json
  // Si liké
  {
    "message": "Toggled to liked.",
    "status": "liked",
    "like": { /* ... objet Like ... */ },
    "likes_count": 26
  }
  // Si unliké
  {
    "message": "Toggled to unliked.",
    "status": "unliked",
    "likes_count": 25
  }
  ```

## Suivis (Follows) - `ijideals/followable`
Préfixe: `/api` (implicite)
Middleware: `auth:sanctum`

### 1. Suivre un Utilisateur
- **Endpoint:** `/users/{user_id}/follow`
- **Méthode:** `POST`
- **Réponse Succès (`200 OK`):** Message de succès.

### 2. Ne plus Suivre un Utilisateur
- **Endpoint:** `/users/{user_id}/unfollow`
- **Méthode:** `DELETE`
- **Réponse Succès (`200 OK`):** Message de succès.

### 3. Basculer l'État de Suivi d'un Utilisateur (Toggle)
- **Endpoint:** `/users/{user_id}/toggle-follow`
- **Méthode:** `POST`
- **Réponse Succès (`200 OK`):** Message indiquant l'état (suivi ou non suivi).

### 4. Vérifier si l'Utilisateur Actuel Suit un Autre Utilisateur
- **Endpoint:** `/users/{user_id}/is-following`
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):**
  ```json
  {
    "is_following": true // ou false
  }
  ```

### 5. Lister les Abonnés (Followers) d'un Utilisateur
- **Endpoint:** `/users/{user_id}/followers`
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):** Tableau d'objets User.

### 5. Lister les Abonnements (Followings) d'un Utilisateur
- **Endpoint:** `/users/{user_id}/followings`
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):** Tableau d'objets User.

## Commentaires - `ijideals/commentable`
Préfixe: `/api/v1/comments` (configurable)

### 1. Lister les Commentaires d'un Objet Commentable
- **Endpoint:** `/{commentable_type}/{commentable_id}`
  - Exemple pour un post: `/api/v1/comments/posts/123`
- **Méthode:** `GET`
- **Paramètres de Requête (Optionnel):** `page={numero}` (pour la pagination), `per_page={nombre}`.
- **Réponse Succès (`200 OK`):** Structure paginée de Laravel contenant les commentaires.
  ```json
  {
    "data": [
      {
        "id": 1,
        "body": "Contenu du commentaire", // 'content' dans le code du contrôleur, 'body' dans le modèle Comment.php
        "commenter": { /* ... objet utilisateur (auteur du commentaire) ... */ },
        "created_at": "...",
        "children": [ /* ... commentaires enfants ... */ ] // si réponses imbriquées
      }
    ]
    // ... infos de pagination ...
  }
  ```

### 2. Poster un Commentaire
- **Endpoint:** `/{commentable_type}/{commentable_id}`
  - Exemple pour un post: `/api/v1/comments/posts/123`
- **Méthode:** `POST`
- **Middleware:** `auth:api` (ex: `auth:sanctum`)
- **Corps de la Requête (JSON):**
  ```json
  {
    "content": "Contenu du nouveau commentaire.", // Le contrôleur valide 'content'
    // "parent_id": null // ou ID du commentaire parent pour une réponse
  }
  ```
- **Réponse Succès (`201 Created`):** L'objet Commentaire créé (avec `commenter` chargé).

### 3. Mettre à Jour un Commentaire
- **Endpoint:** `/{comment_id}`
- **Méthode:** `PUT` ou `PATCH`
- **Middleware:** `auth:api` (nécessite d'être l'auteur)
- **Corps de la Requête (JSON):** `{ "content": "Contenu mis à jour." }`
- **Réponse Succès (`200 OK`):** L'objet Commentaire mis à jour (avec `commenter` chargé).

### 4. Supprimer un Commentaire
- **Endpoint:** `/{comment_id}`
- **Méthode:** `DELETE`
- **Middleware:** `auth:api` (nécessite d'être l'auteur)
- **Réponse Succès (`204 No Content` ou message JSON):** Message de succès.

---
*Cette documentation est une première ébauche et devra être affinée et complétée au fur et à mesure que les intégrations sont testées et que la structure exacte des réponses API est confirmée.*
