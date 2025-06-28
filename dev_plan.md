# Plan de Développement : Interface Web pour Réseau Social & Commercial

## Objectif Général
Créer une interface web frontend (Next.js) qui utilise et s'harmonise avec le backend Laravel existant, en s'inspirant de l'interface de Twitter/X et en intégrant les fonctionnalités sociales et commerciales fournies par les packages `ijideals/`.

## Références Clés
- Backend: Code source Laravel (racine du projet), en particulier les packages dans `packages/ijideals/` et leurs routes API.
- Frontend: Projet Next.js dans le dossier `frontend/`.
- TODO détaillé: `frontend/TODO.md`.
- Inspiration UI: `template.html` (fourni par l'utilisateur) et l'interface de Twitter/X.

## Environnement et Prérequis
- Backend Laravel fonctionnel et accessible (par défaut `http://localhost:8000`).
- Frontend Next.js (démarré avec `npm run dev` dans `frontend/`, accessible sur `http://localhost:3000`).
- Configuration CORS appropriée sur le backend Laravel pour accepter les requêtes du frontend.

## Phases de Développement

### Phase 1: Authentification Robuste (Priorité Haute)
1.  **Configuration API Client & CSRF (Frontend)**
    *   Mettre en place un client `axios` (ou `fetch` wrapper) dans `frontend/src/lib/api.ts` configuré pour interagir avec le backend Laravel.
    *   Gérer la récupération du cookie CSRF (`/sanctum/csrf-cookie`) avant les requêtes POST d'authentification.
    *   Configurer les appels pour inclure les credentials (cookies).
2.  **Implémentation Page de Connexion (Frontend)**
    *   Connecter `LoginForm.tsx` au `AuthContext`.
    *   Implémenter la logique d'appel API réelle pour `/login` (Fortify) dans `AuthContext`.
    *   Gérer les erreurs de connexion et les afficher à l'utilisateur.
    *   Rediriger vers `/home` (ou autre page principale) après connexion réussie.
3.  **Implémentation Page d'Inscription (Frontend)**
    *   [X] Créer la page `/auth_group/register/page.tsx`.
    *   [X] Créer `RegisterForm.tsx`.
    *   Connecter au `AuthContext`.
    *   Implémenter la logique d'appel API réelle pour `/register` (Fortify) dans `AuthContext`.
    *   Gérer les erreurs d'inscription.
    *   Rediriger après inscription réussie.
4.  **Gestion de la Session Utilisateur (Frontend)**
    *   Au chargement de l'application, appeler `/api/user` pour récupérer l'utilisateur si une session existe.
    *   Mettre à jour `AuthContext` avec les informations de l'utilisateur.
    *   Implémenter la fonction de déconnexion (`/logout` via Fortify) dans `AuthContext`.
5.  **Protection des Routes (Frontend)**
    *   [X] Créer un composant `ProtectedRouteWrapper.tsx` qui gère la redirection si l'utilisateur n'est pas authentifié.
    *   [X] Envelopper les layouts des groupes de routes protégées (ex: `main_group`) avec `ProtectedRouteWrapper`.
    *   [X] Afficher conditionnellement des éléments UI (ex: Navbar) en fonction de l'état d'authentification (vérifié et amélioré).

### Phase 2: Fonctionnalités Sociales de Base (Priorité Haute)
1.  **Affichage du Fil d'Actualité (Frontend)**
    *   [X] Remplacer les données mockées dans `HomePage` par un appel API réel à `/api/v1/feed`.
    *   [X] Gérer le chargement, les erreurs, et l'affichage des posts via `PostCard`.
    *   [X] Vérifier/Ajuster `PostCard.tsx` et types `Post` pour correspondre aux données API (commentaires ajoutés aux types pour vérification ultérieure).
    *   [ ] Implémenter la pagination ou le défilement infini (Optionnel - à détailler en fonction de l'API).
2.  **Création de Posts (Frontend)**
    *   [X] Développer `NewPostForm.tsx` (avec saisie, compteur, appel API de base).
    *   [X] Intégrer `NewPostForm.tsx` dans `HomePage.tsx`.
    *   [X] Appel API à `POST /api/v1/social/posts` pour créer un nouveau post (implémenté dans `NewPostForm`).
    *   [ ] Mettre à jour le fil d'actualité localement ou re-fetcher (Option 1 implémentée via callback, à affiner).
3.  **Affichage des Profils Utilisateurs (Frontend)**
    *   Remplacer les données mockées dans `UserProfilePage` et `UserProfileHeader`.
    *   Appel API à `GET /api/users/{username}/profile` (via `ijideals/user-profile`).
    *   Appel API pour lister les posts de l'utilisateur (`ijideals/social-posts`).
4.  **Interactions sur les Posts (Likes, Commentaires - Frontend)**
    *   **Likes**: Implémenter la logique de like/unlike sur `PostCard` en appelant `POST /api/posts/{post}/like` et `DELETE /api/posts/{post}/like` (via `ijideals/likeable`). Mettre à jour l'UI en conséquence.
    *   **Commentaires (Base)**: Afficher le nombre de commentaires. Sur la page de détail d'un post (`/posts/[post_id]` ou `/status/[post_id]`), lister les commentaires (API via `ijideals/commentable`) et ajouter un formulaire pour poster un nouveau commentaire.

### Phase 3: Fonctionnalités Sociales Avancées (Priorité Moyenne)
1.  **Système de Suivi (Follow/Unfollow - Frontend)**
    *   Ajouter des boutons "Suivre"/"Ne plus suivre" sur `UserProfileHeader`.
    *   Appels API à `POST /api/users/{user}/follow` et `DELETE /api/users/{user}/unfollow` (via `ijideals/followable`).
    *   Mettre à jour l'UI et les comptes de followers/followings.
2.  **Hashtags (Frontend)**
    *   Rendre les hashtags cliquables dans `PostCard`.
    *   Créer une page `/tags/[tagname]` pour afficher les posts contenant un hashtag spécifique (API via `ijideals/hashtag-system`).
3.  **Notifications (Frontend)**
    *   Créer la page `/notifications` pour afficher les notifications de l'utilisateur (API via `ijideals/notification-system`).
    *   Marquer les notifications comme lues.
4.  **Recherche (Frontend)**
    *   Intégrer une barre de recherche dans la `Navbar` ou une page dédiée `/search`.
    *   Appel API à l'endpoint de recherche de `ijideals/search-engine` pour rechercher utilisateurs, posts, hashtags.

### Phase 4: Fonctionnalités Commerciales (Priorité Moyenne à Basse, selon focus)
1.  **Visualisation des Boutiques et Produits (Frontend)**
    *   Créer des pages publiques pour les boutiques (`/shop/[shopId]`) affichant les infos de la boutique et ses produits (API via `ijideals/shop-manager` et `ijideals/catalog-manager`).
    *   Composants `ShopCard`, `ProductCard`.
    *   Page de détail d'un produit.
2.  **Gestion Basique des Boutiques (pour propriétaires - Frontend)**
    *   Interface pour modifier les informations de sa boutique.
    *   Interface pour ajouter/modifier des produits simples.

### Phase 5: Améliorations et Finalisation (Continu)
1.  **Styling Complet et Responsive Design.**
2.  **Gestion des Erreurs et Feedback Utilisateur.**
3.  **Optimisations des Performances.**
4.  **Tests (Unitaires, Intégration, E2E).**
5.  **Accessibilité (a11y).**

## Itérations et Priorisation
- Chaque phase sera décomposée en tâches plus petites.
- Les priorités indiquées peuvent être ajustées en fonction des retours.
- L'accent sera mis sur la fourniture de fonctionnalités de base fonctionnelles avant de passer aux fonctionnalités plus avancées ou complexes.

### Étape Corrective : Refactorisation Terminologique

1.  [X] **Mettre à jour `dev_plan.md`**: Remplacer systématiquement la terminologie "Tweet" (et dérivés comme "TweetCard", "NewTweetForm") par "Post" (et dérivés "PostCard", "NewPostForm") pour s'aligner sur la nomenclature du package `ijideals/social-posts`.
2.  [X] **Mettre à jour `frontend/TODO.md`**: Appliquer la même refactorisation terminologique.
3.  **Refactoriser les types Frontend**:
    *   [X] Renommer `frontend/src/lib/types/tweet.ts` en `frontend/src/lib/types/post.ts`.
    *   [X] Dans le nouveau `post.ts`, renommer l'interface `Tweet` en `Post` et `TweetAuthor` en `PostAuthor`.
4.  **Refactoriser les composants Frontend**:
    *   [X] Renommer `frontend/src/components/feed/TweetCard.tsx` en `frontend/src/components/feed/PostCard.tsx`.
    *   [X] Mettre à jour le contenu de `PostCard.tsx` pour utiliser les types et props renommés.
    *   [X] Renommer `frontend/src/components/feed/NewTweetForm.tsx` en `frontend/src/components/feed/NewPostForm.tsx`.
    *   [X] Mettre à jour le contenu de `NewPostForm.tsx` pour utiliser les types et props renommés.
5.  **Mettre à jour les utilisations des composants et types renommés** dans les pages (ex: `HomePage`, `UserProfilePage`).
    *   [X] `HomePage.tsx` mis à jour.
    *   [X] `UserProfilePage.tsx` mis à jour.
6.  **Soumettre les modifications de refactorisation** avec un message de commit clair.
7.  **Reprendre le `dev_plan.md`** à la Phase 2, Étape 2 (maintenant concernant `NewPostForm`).
