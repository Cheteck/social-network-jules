# Frontend Twitter Clone

Ce dossier contient le frontend Next.js pour l'application clone de Twitter.
Il est conçu pour interagir avec le backend Laravel situé à la racine du projet.

## Prérequis

- Node.js (version recommandée par Next.js, généralement la dernière LTS)
- npm ou yarn

## Démarrage

1.  Naviguez dans le dossier `frontend`:
    ```bash
    cd frontend
    ```
2.  Installez les dépendances (si ce n'est pas déjà fait) :
    ```bash
    npm install
    # ou
    # yarn install
    ```
3.  Lancez le serveur de développement :
    ```bash
    npm run dev
    # ou
    # yarn dev
    ```

L'application devrait être accessible sur `http://localhost:3000` (ou un autre port si celui-ci est occupé).

## Backend

Assurez-vous que le backend Laravel est en cours d'exécution et accessible, car ce frontend en dépend pour les données et l'authentification.
Par défaut, le frontend s'attendra à ce que le backend soit sur `http://localhost:8000`. Si ce n'est pas le cas, vous devrez ajuster les appels API dans le code.
