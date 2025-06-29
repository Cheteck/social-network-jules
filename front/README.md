# Frontend – Réseau Social & Commercial (inspiré de X/Twitter)

Ce dossier contient le frontend Next.js/TypeScript/Tailwind du projet social-network, inspiré de l'interface de Twitter (X).

## Structure principale

- `src/app/` : Pages principales (feed, auth, profil, notifications, shops, etc.)
- `src/components/` : Composants réutilisables (Sidebar, Navbar, PostCard, etc.)
- `src/lib/api/` : Fonctions d'appel à l'API Laravel (auth, posts, feed, etc.)
- `public/` : Assets statiques (icônes, logos, images)

## Pages prévues
- `/` : Fil d'actualité (feed)
- `/login`, `/register`, `/forgot-password` : Authentification
- `/profile`, `/settings` : Profil et paramètres utilisateur
- `/[username]` : Profil public
- `/notifications` : Notifications
- `/search` : Recherche
- `/shops/[shopId]` : Pages boutiques

## Lancer le projet

```bash
cd Front
npm install
npm run dev
```

## Objectif

- Interface moderne, responsive, inspirée de X/Twitter
- Connexion à l'API Laravel (voir `../api_documentation.md`)
- Extensible (groupes, messagerie, etc.)

---
Pour toute modification, voir la documentation API et la roadmap dans le dossier racine.
