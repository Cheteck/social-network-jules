## Instructions

Voici une liste de fonctionnalités à implémenter, inspirées de l'interface de Twitter, pour enrichir l'application :

### Fonctionnalités Essentielles (MVP)

*   **Fil d'actualité (Timeline)** :
    *   Afficher les publications des utilisateurs suivis, triées par date.
    *   Permettre le défilement infini (pagination des publications).
*   **Création de publication** :
    *   Formulaire pour rédiger et publier un nouveau message (avec une limite de caractères, comme un tweet).
    *   Possibilité d'ajouter des images ou des vidéos (à considérer pour une phase ultérieure si complexe).
*   **Profil Utilisateur** :
    *   Afficher les informations de l'utilisateur (nom, bio, photo de profil).
    *   Lister toutes les publications de l'utilisateur.
    *   Permettre la modification du profil (nom, bio, photo).
*   **Interactions avec les publications** :
    *   Boutons "J'aime" (Like) et affichage du nombre de likes.
    *   Boutons "Commenter" (Reply) et affichage des commentaires sous une publication.
    *   Bouton "Repartager" (Retweet/Share).
*   **Suivi d'utilisateurs** :
    *   Bouton "Suivre" / "Ne plus suivre" sur les profils ou les publications.
    *   Affichage du nombre d'abonnés et d'abonnements.

### Fonctionnalités Avancées

*   **Recherche** :
    *   Barre de recherche pour trouver des utilisateurs ou des publications par mots-clés/hashtags.
*   **Notifications** :
    *   Afficher les notifications (nouveaux abonnés, likes, commentaires, mentions).
*   **Messages privés (DM)** :
    *   Interface pour envoyer et recevoir des messages directs entre utilisateurs.
*   **Tendances / Hashtags** :
    *   Section affichant les sujets populaires ou les hashtags tendance.
*   **Gestion des médias** :
    *   Uploader et afficher des images/vidéos dans les publications.

### Améliorations Techniques / UX

*   **Optimisation des performances** :
    *   Mise en cache des données côté client.
    *   Optimisation du chargement des images (lazy loading).
*   **Accessibilité** :
    *   Assurer la conformité WCAG pour une meilleure accessibilité.
*   **Tests** :
    *   Ajouter des tests unitaires et d'intégration pour les composants critiques.
*   **Design System** :
    *   Mettre en place un système de design cohérent pour les composants réutilisables.
