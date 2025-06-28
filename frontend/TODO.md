# TODO - Frontend Social & Commercial Platform

## Principes Directeurs & Consignes
- **Terminologie :** Toujours utiliser la nomenclature définie par les packages backend `ijideals` (ex: "Post" au lieu de "Tweet"). Se référer à l'analyse des modèles backend en cas de doute.
- **Mise à Jour des Plans :** Mettre à jour `dev_plan.md` (racine) et ce fichier `frontend/TODO.md` après chaque analyse significative des packages backend, découverte d'API, ou si de nouvelles informations/décisions impactent le plan de développement frontend.
- **Documentation API :** Consulter et contribuer à `api_documentation.md` (racine) pour assurer une compréhension partagée des endpoints backend.

Liste des tâches pour le développement du frontend, inspiré de Twitter et des fonctionnalités commerciales.

## Phase 1: Initialisation et Authentification
- [X] Explorer et documenter les endpoints API du backend Laravel. (Partiellement fait, à continuer)
- [X] Mettre en place la structure de base des dossiers (pages, composants, services, hooks).
- [ ] Créer la page de Connexion (`/auth_group/login`).
    - [X] Formulaire de connexion UI de base.
    - [ ] Validation des champs (côté client avec Zod/React Hook Form).
    - [ ] Service d'appel à l'API de connexion Laravel (`/login` via Fortify, gestion CSRF).
    - [X] Gestion de l'état d'authentification (React Context / Zustand - base mise en place).
    - [ ] Stockage sécurisé du token/session.
    - [ ] Redirection après connexion.
- [ ] Créer la page d'Inscription (`/auth_group/register`).
    - [ ] Formulaire d'inscription UI.
    - [ ] Validation des champs.
    - [ ] Service d'appel à l'API d'inscription Laravel (`/register` via Fortify).
- [ ] Mettre en place la redirection globale si l'utilisateur est/n'est pas authentifié (Layouts, Middleware Next.js).
- [X] Créer un composant Barre de Navigation (`Navbar.tsx`).
    - [X] Afficher les liens Connexion/Inscription si non authentifié.
    - [ ] Afficher le nom d'utilisateur / lien de profil et déconnexion si authentifié.
    - [ ] Intégrer la recherche globale.
- [X] Créer un composant Sidebar (`Sidebar.tsx`).
    - [X] Liens de navigation principaux.
    - [ ] Logique d'ouverture/fermeture sur mobile.

## Phase 2: Fonctionnalités Sociales de Base (Posts)
- [X] Créer un composant `PostCard.tsx` pour afficher un post.
    - [X] Afficher avatar, nom, @username, contenu, date.
    - [ ] Actions : like, retweet, commentaire (connexion API).
    - [ ] Affichage des médias attachés (via `ijideals/media-uploader`).
- [X] Créer la page principale du Fil d'actualité (`/main_group/home`).
    - [ ] Récupérer et afficher la liste des posts depuis l'API (`ijideals/news-feed-generator` ou `ijideals/social-posts`).
    - [ ] Gérer le chargement, les erreurs, et la pagination/défilement infini.
- [ ] Créer un composant `NewPostForm.tsx`.
    - [ ] Permettre à un utilisateur authentifié de poster un nouveau post.
    - [ ] Validation (longueur, etc.).
    - [ ] Option d'upload de médias (intégration `ijideals/media-uploader`).
    - [ ] Mettre à jour le flux après la création.
- [ ] Page de détail d'un Post (`/main_group/posts/[post_id]` ou `/main_group/status/[post_id]`).
    - [X] Afficher le post principal (appel API `GET /api/v1/social/posts/{post_id}`).
    - [ ] Lister les commentaires pour le post (appel API `GET /api/v1/comments/posts/{post_id}`).
    - [ ] Formulaire pour ajouter un nouveau commentaire (appel API `POST /api/v1/comments/posts/{post_id}` avec `{ content: "..." }`).
    - [ ] (Optionnel) UI pour répondre à un commentaire (utilisation de `parent_id`).
    - [ ] (Optionnel) UI pour modifier/supprimer ses propres commentaires.
- [ ] Intégrer les Likes (via `ijideals/likeable`) sur les posts et commentaires.
- [ ] Intégrer les Hashtags (via `ijideals/hashtag-system`).
    - [ ] Affichage des hashtags cliquables dans les posts.
    - [ ] Page dédiée pour les posts par hashtag.

## Phase 3: Profils Utilisateurs
- [X] Créer une page de profil utilisateur (`/main_group/[username]`).
    - [X] Afficher les informations du profil (nom, bio, stats - base UI faite).
    - [ ] Récupérer les données du profil depuis l'API (`ijideals/user-profile`).
    - [ ] Afficher la liste des posts de cet utilisateur.
    - [ ] Afficher la liste des médias de l'utilisateur.
    - [ ] Afficher les posts likés par l'utilisateur.
- [ ] Fonctionnalité de Suivi/Abonnement (via `ijideals/followable`).
    - [ ] Sur `UserProfileHeader`, ajouter un bouton "Suivre"/"Ne plus suivre" ou "Toggle Follow".
    - [ ] Implémenter les appels API pour suivre (`POST /api/users/{user_id}/follow`), ne plus suivre (`DELETE /api/users/{user_id}/unfollow`), ou basculer (`POST /api/users/{user_id}/toggle-follow`).
    *   [ ] Utiliser `GET /api/users/{user_id}/is-following` pour déterminer l'état initial du bouton.
    - [ ] Mettre à jour l'UI (texte du bouton, compteurs de followers/abonnements) de manière optimiste et après confirmation de l'API.
    - [ ] (Optionnel) Créer des pages ou modales pour afficher la liste des abonnés (`GET /users/{user_id}/followers`).
    - [ ] (Optionnel) Créer des pages ou modales pour afficher la liste des abonnements (`GET /users/{user_id}/followings`).
- [ ] Paramètres du profil utilisateur (`/main_group/settings/profile` - via `ijideals/user-settings` et `ijideals/user-profile`).
    - [ ] Modification des informations du profil (bio, nom, etc.).
    - [ ] Modification de l'avatar et de la bannière (intégration `ijideals/media-uploader`).

## Phase 4: Notifications
- [ ] Page des Notifications (`/main_group/notifications` - via `ijideals/notification-system`).
    - [ ] Afficher la liste des notifications (nouveaux likes, commentaires, abonnés, etc.).
    - [ ] Marquer les notifications comme lues.
    - [ ] (Optionnel) Notifications en temps réel (polling ou WebSockets si backend le supporte).
- [ ] Préférences de notification dans les paramètres utilisateur (via `ijideals/user-settings`).

## Phase 5: Recherche
- [ ] Barre/Page de Recherche (via `ijideals/search-engine`).
    - [ ] Rechercher des utilisateurs, posts, hashtags.
    - [ ] (Si pertinent) Rechercher des produits et boutiques.
    - [ ] Affichage des résultats de recherche.

## Phase 6: Fonctionnalités Commerciales (Boutiques et Produits)

### Gestion des Boutiques (Frontend pour `ijideals/shop-manager`)
- [ ] Page de création de boutique.
- [ ] Page de gestion de boutique (pour les propriétaires/admins).
    - [ ] Modifier les informations de la boutique (nom, description, logo, bannière - via `ijideals/media-uploader`).
    - [ ] Gérer les membres de la boutique et leurs rôles.
- [ ] Page de profil public d'une boutique (`/shop/[shop_slug_or_id]`).
    - [ ] Afficher les informations de la boutique.
    - [ ] Lister les produits de la boutique.
    - [ ] Lister les posts de la boutique.
- [ ] Flux de posts spécifiques à une boutique.

### Catalogue de Produits (Frontend pour `ijideals/catalog-manager`)
- [ ] Affichage des produits sur la page boutique et potentiellement dans des posts.
    - [ ] Composant `ProductCard`.
    - [ ] Page de détail d'un produit (`/product/[product_id]`).
        - Afficher informations, images (via `ijideals/media-uploader`), variantes, options.
- [ ] (Pour admins de boutique) Interface de gestion des produits.
    - [ ] Créer/modifier des produits.
    - [ ] Gérer les catégories de produits.
    - [ ] Gérer les options et variantes de produits.
- [ ] (Optionnel) Fonctionnalité de panier et de commande (si `ijideals/order-manager` est utilisé).

## Phase 7: Améliorations Générales et Style
- [X] Appliquer un style inspiré de Twitter/X avec Tailwind CSS (base faite, à continuer).
- [ ] Améliorer l'UX/UI sur tous les composants et pages.
- [ ] Ajouter la gestion des erreurs et des notifications utilisateur (toaster, messages inline).
- [ ] Optimiser les performances (Lazy loading, code splitting, memoization, etc.).
- [ ] Responsive design complet.
- [ ] Internationalisation (i18n) si nécessaire.
- [ ] Accessibilité (a11y).

## Phase 8: Tests
- [ ] Mettre en place Jest et React Testing Library.
- [ ] Écrire des tests unitaires pour les fonctions utilitaires et les hooks.
- [ ] Écrire des tests d'intégration pour les composants critiques (LoginForm, PostCard, UserProfileHeader, NewPostForm, etc.).
- [ ] (Optionnel) Mettre en place des tests E2E avec Cypress ou Playwright pour les flux utilisateurs clés.

## Backlog / Idées Futures
- [ ] Messagerie privée (`ijideals/messaging`).
- [ ] Groupes et communautés (`ijideals/group-manager`).
- [ ] Tendances (`ijideals/trending-system`).
- [ ] ... (autres fonctionnalités de `TODO_FEATURES.md` du backend)
