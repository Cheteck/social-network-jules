# Liste des Fonctionnalités et Packages Potentiels pour le Réseau Social

Cette liste est inspirée des suggestions de l'utilisateur et des meilleures pratiques pour un développement modulaire.

## Modules/Packages Réutilisables Suggérés par l'Utilisateur :

### 1. Interactions Sociales
- [ ] **FriendshipSystem** : Demandes d’amis, liste d’amis.
- [x] **FollowSystem** : Abonnements (follow/unfollow). (Implémenté avec `ijideals/followable`)
- [ ] **NotificationSystem** : Notifications en temps réel (WebSocket ou polling).
- [ ] **Messaging** : Messagerie privée (1-to-1 et groupes).
- [ ] **MentionSystem** : Mentionner des utilisateurs dans les posts/comments.

### 2. Profil Utilisateur
- [x] **UserProfile** : Gestion du profil utilisateur (avatar, bio, informations personnelles). (Implémenté avec `ijideals/user-profile` pour les champs textuels). *Factory à vérifier/compléter.*
- [x] **UserSettings** : Paramètres utilisateur (préférences de notification, etc.). (Implémenté avec `ijideals/user-settings`). *Factory à créer/compléter.*
- [ ] **UserVerification** : Vérification d'identité (documents, selfie).

### 3. Contenu & Publications
- [x] **PostManager** : Création, édition, suppression des posts (texte). (Couvert par `ijideals/social-posts`)
    - Peut maintenant avoir un `Shop` comme auteur. *Factory OK.*
- [x] **CommentSystem** : Gestion des commentaires (réponses, réactions). (Implémenté avec `ijideals/commentable`). *Factory OK.*
- [x] **LikeSystem** : Système de likes/upvotes sur les posts et commentaires. (Implémenté avec `ijideals/likeable`). *Factory OK.*
- [x] **MediaUploader** : Upload et gestion des médias (images, GIFs). (Implémenté avec `ijideals/media-uploader`, intégré avec PostManager, UserProfile, ShopManager et CatalogManager). *Factory pour `Media` OK (basique).*
- [x] **HashtagSystem** : Gestion des hashtags et recherche par tags. (Implémenté avec `ijideals/hashtag-system` - Phase 1: création, association aux posts, API de base).
- [x] **CatalogManager (Product System)** : Gestion des produits/services pour les boutiques. (Implémenté avec `ijideals/catalog-manager` - MVP avec options/variantes).
    -  Modèle Produit, Catégorie, Option, ValeurOption, Variante. *Factories OK.*
    -  Association à une boutique (`Shop`).
    -  CRUD API pour les produits par les admins/éditeurs de boutique.
    *   Modèle `Category` pour les produits (globales, hiérarchiques), avec gestion CRUD simple par admin plateforme.
    -  Produits cherchables via `search-engine`.
    - [ ] **Gestion des Spécifications Produits** : Attributs non générateurs de variantes (ex: dimensions, poids).
    - [ ] **Produits aux Enchères (`ijideals/auction-system`)**: Extension pour permettre les ventes aux enchères.
    - [ ] **Stickers/Badges Produits (`ijideals/product-stickers`)**: Affichage dynamique de badges (promo, nouveau, etc.).

### 4. Découverte & Recherche
- [x] **SearchEngine** : Recherche globale (utilisateurs, posts, boutiques, produits). (Implémenté avec `ijideals/search-engine` via Laravel Scout).
- [x] **FeedGenerator (Intelligent News Feed)** : Génération du fil d'actualité personnalisé et pertinent. (Implémenté - Phase 2 avec classement et découverte simple).
- [ ] **TrendingSystem** : Tendances (hashtags, posts populaires).

### 5. Groupes & Communautés
- [ ] **GroupManager** : Création et gestion de groupes.
- [ ] **GroupMembership** : Adhésion, rôles dans les groupes.
- [ ] **EventManager** : Événements dans les groupes (dates, RSVP).
- [x] **ShopManager (Boutiques/Pages Commerciales)** : Gestion de boutiques/pages type Facebook. (Implémenté avec `ijideals/shop-manager` - MVP, Policies intégrées).
    -  Création/gestion de la boutique (infos, image de couverture, etc.). *Factory OK.*
    -  Gestion des rôles d'administration granulée pour la boutique.
    -  Publication de contenu spécifique à la boutique (posts, produits).
    -  Potentiellement intégration avec un système de produits/commandes.

### 6. Modération & Signalement
- [ ] **ContentModeration** : Filtrage de contenu (mots interdits, images NSFW).
- [ ] **ReportSystem** : Signalement de posts/utilisateurs.
- [ ] **AdminDashboard** : Backoffice pour la modération.

### 7. Performance & Optimisation
- [ ] **CachingSystem** : Cache des données fréquemment accédées.
- [ ] **APICaching** : Cache des réponses API (avec Laravel Query Builder ou Redis).
- [x] **ImageOptimizer** : Optimisation des images avant stockage (Basique, via `ijideals/media-uploader` et Intervention Image).

### 8. Internationalisation
- [x] **Localization (Static Strings)** : Traductions multi-langues (en, fr) pour les messages API des packages `likeable`, `commentable`, `media-uploader`, `notification-system`, `news-feed-generator`. Middleware `SetLocale` implémenté.
    -  Prochaine étape : Tests pour vérifier les traductions.
    -  Futur : Traduction du contenu dynamique (posts, etc.), gestion des formats de date/nombre.
- [ ] **TimezoneHandler** : Gestion des fuseaux horaires.

### 9. Analytics & Statistiques
- [ ] **UserAnalytics** : Statistiques d’utilisation (activité, temps passé).
- [ ] **PostAnalytics** : Vues, likes, partages des posts.

### 10. Monétisation & Fonctionnalités Commerciales Avancées
- [ ] **SubscriptionSystem** : Abonnements premium pour utilisateurs ou boutiques.
- [ ] **AdManager** : Publicités et emplacements.
- [ ] **OrderManager (`ijideals/order-manager`)**: Gestion des commandes pour les produits des boutiques.
    - Processus de panier et de paiement (intégration future avec des gateways).
    - Suivi des statuts de commande.
- [ ] **InventoryManager (`ijideals/inventory-manager`)**: Gestion avancée des stocks.
    - Historique des mouvements, alertes de stock bas.
- [ ] **ShopReviews (`ijideals/shop-reviews`)**: Avis et évaluations des boutiques.
- [ ] **ProductReviews (`ijideals/product-reviews`)**: Avis et évaluations des produits.
- [ ] **DiscountPromotions (`ijideals/discount-promotions`)**: Gestion des réductions et codes promo.

### 11. Intégrations Externes
- [ ] **APIGateway** : Gestion des appels API externes (Twitter, Stripe, etc.).
- [ ] **WebhookSystem** : Gestion des webhooks entrants/sortants.

### 12. Tests & Déploiement (Pratiques, pas des packages fonctionnels)
- [x] **FeatureTests** : Tests automatisés pour chaque module. (En place pour les modules développés)
- [ ] **DeploymentScripts** : Scripts CI/CD pour déploiement.

---
## Problèmes Connus / Dette Technique :

-   **Tests d'intégration API (`SocialPostsApiTest`)** : Échouent actuellement avec des erreurs 500. La cause racine doit être investiguée (potentiellement liée à la configuration de test, aux factories, ou à des interactions entre packages non détectées par les tests unitaires). La logique de `syncHashtags` dans `PostController` est temporairement commentée pour le débogage.
-   **Package `catalog-manager`** :
    -   Les routes sont temporairement désactivées dans le `CatalogManagerServiceProvider` en raison d'une `TypeError` lors de leur chargement. Cela doit être corrigé.
    -   Une migration (`create_categories_table`) avait une variable non définie qui a été corrigée.
-   **Stabilité générale des tests** : Plusieurs corrections ont été nécessaires dans divers packages (`likeable`, `user-settings`, `user-profile`, `media-uploader`) et dans le modèle `User` principal pour permettre aux tests de base et à `composer update` de s'exécuter. Cela suggère une potentielle fragilité ou un manque de tests approfondis pour certains des packages existants.

---

## État Actuel des Packages Implémentés par Jules :

1.  **`ijideals/followable`** (Système d'abonnements)
    *   Statut : Implémenté.
    *   Permet aux utilisateurs de suivre/arrêter de suivre d'autres utilisateurs.

2.  **`ijideals/user-profile`** (Profils Utilisateurs Basiques)
    *   Statut : Implémenté.
    *   Gestion des champs textuels du profil (bio, etc.).

3.  **`ijideals/social-posts`** (Gestion des Posts Textuels)
    *   Statut : Implémenté.
    *   Permet la création, l'affichage des posts textuels.

4.  **`ijideals/likeable`** (Système de Likes)
    *   Statut : Implémenté.
    *   Permet de liker/unliker des modèles (ex: Posts).
    *   API et tests inclus.

5.  **`ijideals/commentable`** (Système de Commentaires)
    *   Statut : Implémenté.
    *   Permet de commenter des modèles (ex: Posts), supporte les réponses imbriquées.
    *   API et tests inclus.

6.  **`ijideals/media-uploader`** (Gestionnaire de Médias)
    *   Statut : Implémenté.
    *   Permet d'uploader des médias (images initialement) et de les associer aux modèles (ex: User pour avatar, Post pour images de post).
    *   Supporte les collections, l'optimisation d'image basique.
    *   API et tests inclus.
    *   Intégré avec `UserProfile` (pour avatar via `User::getFirstMedia('avatar')`) et `PostManager` (pour images de post via `Post::getMedia('images')`).
    *   Messages API traduits (en, fr).

7.  **`ijideals/news-feed-generator`** (Fil d'Actualité)
    *   Statut : Implémenté (Phase 2 - avec classement et découverte simple).
    *   Génère un fil d'actualité basé sur les suivis et des posts populaires, classé par score (récence, engagement).
    *   API et tests inclus.
    *   Structure de traduction en place (en, fr).

8.  **`ijideals/notification-system`** (Système de Notifications)
    *   Statut : Implémenté.
    *   Notifications en base de données pour likes, commentaires, nouveaux followers.
    *   API pour gestion des notifications (lister, marquer comme lues).
    *   Intégration via événements.
    *   Messages API traduits (en, fr).

9.  **`ijideals/search-engine`** (Moteur de Recherche)
    *   Statut : Implémenté (MVP avec Laravel Scout et driver database).
    *   Permet la recherche globale sur les Users, Posts, Shops et Products.
    *   API et tests inclus.

10. **`ijideals/shop-manager`** (Gestion de Boutiques/Pages Commerciales)
    *   Statut : Implémenté (MVP avec Policies).
    *   Création/gestion de boutiques avec propriétaire.
    *   Utilisation de `spatie/laravel-permission` (mode "teams" avec `shop_id`) pour rôles granulés.
    *   Gestion des membres de la boutique et de leurs rôles.
    *   Intégration avec `media-uploader` pour logo/couverture.
    *   Les boutiques peuvent être auteurs de posts via `social-posts`.
    *   Les boutiques sont cherchables. *Factory OK.*
    *   API et tests inclus.

11. **`ijideals/catalog-manager`** (Gestion de Catalogue de Produits)
    *   Statut : Implémenté (MVP avec options et variantes).
    *   Gestion des produits (nom, desc, prix, SKU, stock, images, options, variantes) liés à une boutique.
    *   Gestion des catégories de produits globales et hiérarchiques.
    *   API CRUD pour catégories, options, produits, et variantes.
    *   Intégration avec `media-uploader` pour images produits/variantes.
    *   Produits cherchables. *Factories OK.*
    *   API et tests inclus.

12. **`ijideals/user-settings`** (Paramètres Utilisateur)
    *   Statut : Implémenté (MVP).
    *   Permet aux utilisateurs de gérer des paramètres clé-valeur (ex: préférences de notification).
    *   Trait `HasSettings` pour le modèle User. API pour lire/mettre à jour les paramètres.
    *   Intégration avec `notification-system` pour respecter les préférences de notification en BDD.
    *   Tests inclus. *Factory pour UserSetting à créer/compléter.*

13. **`ijideals/hashtag-system`** (Système de Hashtags)
    *   Statut : Implémenté (Phase 1).
    *   Permet la création de hashtags, l'association polymorphique aux modèles (ex: Posts).
    *   Trait `HasHashtags` pour une intégration facile dans les modèles.
    *   API pour lister les hashtags et les posts par hashtag.
    *   Tests unitaires et d'intégration inclus.
    *   Seeder de démonstration pour les hashtags.
    *   Intégré initialement avec `social-posts` (logique de synchronisation dans `PostController` temporairement commentée en raison d'erreurs 500 dans les tests de `social-posts`).

---

## Prochaines Étapes Planifiées (basées sur les discussions) :

1.  **Seeders et Factories**
    *   Statut : TERMINÉ.
    *   Factories créées/mises à jour pour tous les modèles principaux.
    *   `DatabaseSeeder` principal implémenté pour générer un jeu de données complet et cohérent.
    *   Seeders de package pour Rôles/Permissions et Options de Produit sont appelés.

2.  **Améliorations et Raffinements des Packages Existants (Exemples)**
    *   **Media Uploader :** Suppression physique des fichiers dans `Media::delete()`, conversions d'images (thumbnails).
    *   **News Feed Generator :** Affiner l'algorithme de classement, améliorer la découverte (plus de sources, pertinence), stratégies de cache plus avancées.
    *   **Notification System :** Notifications en temps réel (WebSockets), regroupement de notifications similaires, préférences utilisateur plus granulaires (par canal : email, push, db).
    *   **User Profile :** API dédiée pour la gestion de l'avatar/bannière, ajout de plus de champs de profil standards.
    *   **Catalog Manager :** Gestion des prix de variantes plus flexible (ex: prix absolu vs modificateur), interface/logique pour faciliter la création de toutes les combinaisons de variantes.
    *   **Shop Manager**: Permissions plus fines (utilisation des Policies Laravel), gestion des invitations de membres.
    *   **Priorité : Moyenne.**

3.  **Nouveaux Packages/Fonctionnalités Majeures (Exemples)**
    *   `ijideals/messaging-system`
    *   **Priorité : Basse (à planifier après améliorations).**

---

## Suggestions Précédentes (pour référence ou priorisation future) :

- Système d'amis complet (`FriendshipSystem`)
- Etc. (voir liste complète au début du document)

---

### Structure de Package (Exemple) :
```
src/
├── Models/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Resources/
│   ├── views/
│   └── lang/
├── Database/
│   ├── migrations/
│   └── factories/ (ou seeders)
├── Providers/
├── Routes/
└── Tests/
    └── Feature/
    └── Unit/
```

### Outils Recommandés :
- **Laravel Packager** (pour créer des packages facilement)
- **Spatie Laravel Package Tools** (pour une configuration simplifiée)
```
