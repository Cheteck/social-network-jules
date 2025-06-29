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
    // "device_name": "Nom de l'appareil" // Optionnel, si Jetstream API est activé pour émettre des tokens
  }
  ```
- **Réponse Succès:**
  - `200 OK` ou `204 No Content` (Session établie).
  - Si Jetstream API est activé et `device_name` est fourni, peut aussi inclure un token:
    ```json
    {
      "token": "PLAIN_TEXT_API_TOKEN", // Token à utiliser pour les requêtes API suivantes si nécessaire
      // ... potentiellement d'autres données utilisateur ...
    }
    ```
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

---

## Gestion de Compte Utilisateur (Jetstream/Fortify)

Cette section détaille les endpoints pour la gestion du compte utilisateur fournis par Jetstream et Fortify.

### 1. Mettre à Jour les Informations du Profil Utilisateur
- **Endpoint:** `/user/profile-information`
- **Méthode:** `PUT`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "name": "Nouveau Nom", // Requis, string, max:255
    "email": "nouvel.email@example.com" // Requis, email, max:255, unique:users,email
    // D'autres champs peuvent être supportés par Fortify si configurés (ex: username)
  }
  ```
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut, ou l'objet utilisateur mis à jour si personnalisé).
- **Réponse Erreur (Validation):** `422 Unprocessable Entity` avec les erreurs.
- **Réponse Erreur (Non authentifié):** `401 Unauthorized`.

### 2. Mettre à Jour le Mot de Passe Utilisateur
- **Endpoint:** `/user/password`
- **Méthode:** `PUT`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "current_password": "mot_de_passe_actuel", // Requis
    "password": "nouveau_mot_de_passe", // Requis, min:8, confirmed
    "password_confirmation": "nouveau_mot_de_passe" // Requis
  }
  ```
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut).
- **Réponse Erreur (Validation):** `422 Unprocessable Entity` (ex: mot de passe actuel incorrect, non confirmation).
- **Réponse Erreur (Non authentifié):** `401 Unauthorized`.

### 3. Gestion de l'Authentification à Deux Facteurs (2FA)

#### a. Activer la 2FA
- **Endpoint:** `/user/two-factor-authentication`
- **Méthode:** `POST`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut). L'utilisateur devra ensuite scanner le QR code et confirmer.

#### b. Désactiver la 2FA
- **Endpoint:** `/user/two-factor-authentication`
- **Méthode:** `DELETE`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut).

#### c. Récupérer le QR Code et Clé Secrète pour la 2FA
- **Endpoint:** `/user/two-factor-qr-code`
- **Méthode:** `GET`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`.
- **Réponse Succès (`200 OK`):**
  ```json
  {
    "svg": "<svg>...</svg>", // QR code en SVG
    "secretKey": "BASE32_ENCODED_SECRET_KEY" // Clé secrète à entrer manuellement si QR code non utilisable
  }
  ```
  *Note: Jetstream retourne `recovery_codes` ici si 2FA est déjà activée et que l'utilisateur veut voir son QR/secret. Si 2FA n'est pas encore confirmée, il retourne le QR/secret.*
  *Le endpoint `/user/two-factor-secret-key` est aussi parfois utilisé par Fortify pour retourner juste la clé secrète.*

#### d. Confirmer l'Activation de la 2FA
- **Endpoint:** `/user/confirmed-two-factor-authentication` (si Fortify v2.14+) ou via un endpoint challenge après activation.
- **Méthode:** `POST`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "code": "CODE_DE_L_APPLI_AUTH" // Le code OTP généré par l'application d'authentification
  }
  ```
- **Réponse Succès:** `200 OK` ou `204 No Content`.

#### e. Récupérer les Codes de Récupération 2FA
- **Endpoint:** `/user/two-factor-recovery-codes`
- **Méthode:** `GET`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`.
- **Réponse Succès (`200 OK`):**
  ```json
  [
    "CODE_RECUP_1",
    "CODE_RECUP_2",
    // ...
  ]
  ```
  *Note: L'utilisateur doit être authentifié (potentiellement via un code 2FA si déjà activé) pour accéder à ceci.*

#### f. Générer de Nouveaux Codes de Récupération 2FA
- **Endpoint:** `/user/two-factor-recovery-codes`
- **Méthode:** `POST`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Réponse Succès:** `200 OK`. (Les nouveaux codes sont généralement affichés via une session flash dans un contexte web, ou retournés en JSON si l'API est configurée pour).

### 4. Challenge 2FA (Lors de la Connexion)
- **Endpoint:** `/two-factor-challenge`
- **Méthode:** `POST`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Description:** Appelé lorsque la connexion initiale réussit mais que la 2FA est activée. Le backend redirige généralement vers une page de challenge 2FA ou retourne une réponse indiquant que la 2FA est requise. L'API doit être explicitement appelée si le frontend gère cela.
- **Corps de la Requête (JSON):**
  ```json
  {
    // "code": "CODE_DE_L_APPLI_AUTH", // Si l'utilisateur entre le code OTP
    // "recovery_code": "CODE_DE_RECUPERATION" // Si l'utilisateur utilise un code de récupération
  }
  ```
- **Réponse Succès (Session établie):** `204 No Content` ou `200 OK`.
- **Réponse Erreur (Code invalide):** `422 Unprocessable Entity`.

### 5. Gestion de la Photo de Profil (Jetstream)
*Note: Jetstream gère cela via `Livewire` par défaut. Pour une API SPA, des endpoints spécifiques peuvent être nécessaires ou ceux de Fortify peuvent être utilisés si l'action sous-jacente est exposée.*

#### a. Mettre à Jour la Photo de Profil
- **Endpoint:** `/user/profile-photo`
- **Méthode:** `POST`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`. `Content-Type: multipart/form-data`.
- **Corps de la Requête (Form-Data):**
  - `photo`: Fichier image.
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut).
- **Réponse Erreur (Validation):** `422 Unprocessable Entity`.

#### b. Supprimer la Photo de Profil
- **Endpoint:** `/user/profile-photo`
- **Méthode:** `DELETE`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut).

### 6. Vérification d'Email (si activée)

#### a. Envoyer la Notification de Vérification d'Email
- **Endpoint:** `/email/verification-notification`
- **Méthode:** `POST`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Réponse Succès (`202 Accepted` ou `200 OK`):** Message indiquant que l'email a été envoyé.
- **Réponse Erreur (Déjà vérifié / Throttled):** `400 Bad Request` ou `429 Too Many Requests`.

*(Le lien de vérification envoyé par email (`/verify-email/{id}/{hash}`) est généralement une route web avec une signature. Pour une SPA, on peut rediriger vers le frontend après vérification, qui peut ensuite vérifier le statut de l'email de l'utilisateur via `/api/user`.)*

### 7. Suppression de Compte Utilisateur
- **Endpoint:** `/user` (Ou plus communément `/user/delete` ou `/profile/delete` selon l'implémentation spécifique, Fortify le gère via une action `DeleteUser` qui peut être invoquée)
- **Méthode:** `DELETE`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):** (Souvent requis pour confirmer le mot de passe)
  ```json
  {
    "password": "mot_de_passe_actuel_utilisateur"
  }
  ```
- **Réponse Succès:** `200 OK` ou `204 No Content`.
- **Réponse Erreur (Mot de passe incorrect):** `422 Unprocessable Entity` ou `403 Forbidden`.

### 8. Gestion des Tokens d'API Personnels (Jetstream)
*Note: Ces endpoints sont pertinents si la fonctionnalité API de Jetstream est activée, permettant aux utilisateurs de générer des tokens pour des services tiers ou des applications mobiles.*

#### a. Lister les Tokens d'API de l'Utilisateur
- **Endpoint:** `/user/api-tokens`
- **Méthode:** `GET`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`.
- **Réponse Succès (`200 OK`):**
  ```json
  [
    {
      "id": "token_id",
      "name": "Nom du Token",
      "abilities": ["permission1", "permission2"],
      "last_used_at": "YYYY-MM-DD HH:MM:SS" // ou null
    }
    // ... autres tokens
  ]
  ```

#### b. Créer un Token d'API
- **Endpoint:** `/user/api-tokens`
- **Méthode:** `POST`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Corps de la Requête (JSON):**
  ```json
  {
    "name": "Nom du Nouveau Token", // Requis, string
    "permissions": ["permission:create", "permission:read"] // Optionnel, tableau de strings (permissions/abilities)
  }
  ```
- **Réponse Succès (`201 Created`):**
  ```json
  {
    "token": { // L'objet token créé (similaire à la liste, mais sans le token en clair)
      "id": "new_token_id",
      "name": "Nom du Nouveau Token",
      "abilities": ["permission:create", "permission:read"],
      "last_used_at": null
    },
    "plainTextToken": "TOKEN_EN_CLAIR_A_AFFICHER_UNE_SEULE_FOIS" // Le token réel, à copier immédiatement
  }
  ```

#### c. Mettre à Jour les Permissions d'un Token d'API
- **Endpoint:** `/user/api-tokens/{tokenId}`
- **Méthode:** `PUT`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `Content-Type: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Paramètres URL:**
    - `tokenId`: ID du token à mettre à jour.
- **Corps de la Requête (JSON):**
  ```json
  {
    "permissions": ["new:permission1", "new:permission2"] // Tableau des nouvelles permissions
  }
  ```
- **Réponse Succès:** `200 OK` (Pas de corps de réponse par défaut).

#### d. Supprimer un Token d'API
- **Endpoint:** `/user/api-tokens/{tokenId}`
- **Méthode:** `DELETE`
- **Middleware:** `auth:sanctum`
- **Headers Requis:** `Accept: application/json`, `X-XSRF-TOKEN: {valeur du cookie}`.
- **Paramètres URL:**
    - `tokenId`: ID du token à supprimer.
- **Réponse Succès:** `204 No Content`.

---

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

## Notifications - `ijideals/notification-system`
Préfixe: `/api/v1/notifications` (configurable via `notification-system.route_prefix`)
Middleware: `auth:api` (ex: `auth:sanctum`) pour toutes les routes.

### 1. Lister les Notifications de l'Utilisateur
- **Endpoint:** `/`
- **Méthode:** `GET`
- **Paramètres de Requête (Optionnel):**
    - `page={numero}`: Pour la pagination.
    - `per_page={nombre}`: Nombre d'éléments par page (défaut 20).
    - `status=read|unread`: Pour filtrer par statut de lecture.
- **Réponse Succès (`200 OK`):** Structure paginée de Laravel.
  ```json
  {
    "data": [
      {
        "id": "uuid-de-la-notification", // UUID
        "type": "App\\Notifications\\NewFollower", // Nom de la classe de la notification
        "notifiable_type": "App\\Models\\User",
        "notifiable_id": 123,
        "data": {
          // La structure de 'data' varie selon le 'type' de notification
          // Ex: pour NewFollower:
          // "follower_id": 456,
          // "follower_name": "Nom du Follower",
          // "message": "Nom du Follower vous suit maintenant."
          // Ex: pour NewLikeOnPost:
          // "liker_id": 789,
          // "liker_name": "Nom du Liker",
          // "post_id": 101,
          // "post_summary": "Début du contenu du post...",
          // "message": "Nom du Liker a aimé votre post."
        },
        "read_at": null, // ou "YYYY-MM-DD HH:MM:SS" si lue
        "created_at": "YYYY-MM-DD HH:MM:SS",
        "updated_at": "YYYY-MM-DD HH:MM:SS"
      }
    ],
    "links": { /* ... liens de pagination ... */ },
    "meta": { /* ... méta-données de pagination ... */ }
  }
  ```

### 2. Obtenir le Nombre de Notifications Non Lues
- **Endpoint:** `/unread-count`
- **Méthode:** `GET`
- **Réponse Succès (`200 OK`):**
  ```json
  {
    "unread_count": 5
  }
  ```

### 3. Marquer une Notification comme Lue
- **Endpoint:** `/{notificationId}/read` (où `notificationId` est l'UUID)
- **Méthode:** `PATCH`
- **Réponse Succès (`200 OK`):** `{ "message": "Notification marquée comme lue." }`

### 4. Marquer Toutes les Notifications comme Lues
- **Endpoint:** `/mark-all-as-read`
- **Méthode:** `POST`
- **Réponse Succès (`200 OK`):** `{ "message": "Toutes les notifications ont été marquées comme lues." }`

### 5. Supprimer une Notification
- **Endpoint:** `/{notificationId}` (où `notificationId` est l'UUID)
- **Méthode:** `DELETE`
- **Réponse Succès (`200 OK`):** `{ "message": "Notification supprimée avec succès." }`

### 6. Supprimer Toutes les Notifications
- **Endpoint:** `/clear-all`
- **Méthode:** `DELETE`
- **Paramètres de Requête (Optionnel):** `only_read=true` (pour ne supprimer que les lues).
- **Réponse Succès (`200 OK`):** `{ "message": "X notifications supprimées." }`

---
*Cette documentation est une première ébauche et devra être affinée et complétée au fur et à mesure que les intégrations sont testées et que la structure exacte des réponses API est confirmée.*
