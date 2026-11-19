---
id: JF-001
titre: Squelette Laravel + Vue/Inertia déployé sur le VPS
statut: en cours          # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/squelette-laravel
créé: 2026-11-19
mis-à-jour: 2026-11-19
---
## Contexte

Aucun code n'existe encore. WrestlingStory a besoin d'un projet Laravel + Vue 3 (Inertia) + PostgreSQL qui tourne en production avant tout le reste : c'est la base de travail de Julien et de Florentin, et le planning du projet 2 attend une première page déployée et un pipeline refait pour la nouvelle stack. Revue n°1 le 24 novembre.

## Critères d'acceptation

- [ ] Le projet est créé avec le starter kit officiel Laravel + Vue, et `composer run dev` affiche la page d'accueil en local.
- [ ] L'application utilise PostgreSQL et `php artisan migrate` passe sur une base vide.
- [ ] Les commandes de la table « Commandes » de `AGENTS.md` sont confirmées (lancées une fois chacune) ou corrigées.
- [ ] Une PR vers `main` déclenche la CI : lint (Pint, ESLint), tests (`php artisan test`) et build (`npm run build`) ; un job rouge bloque le merge.
- [ ] Un merge sur `main` déploie automatiquement sur le VPS (PHP-FPM derrière Nginx, migrations appliquées).
- [ ] La page d'accueil répond en production, à l'adresse publique du VPS.
- [ ] Aucun secret dans le dépôt : `.env` ignoré par git, secrets du déploiement dans les GitHub Secrets.

## Journal du ticket

- 2026-11-19 : créé. Idée, stack et hébergeur validés par le professeur.
- 2026-11-19 : lot 1 (squelette local) presque fini sur le PC fixe. Laravel 13.43 + Vue/Inertia 3 créé à la racine, PostgreSQL configuré (`wrestlingstory`, `wrestlingstory_test`). Vérifiés : `composer lint:check`, `composer types:check`, `npm run check`, `npm run types:check`, `npm run build`. Non vérifiés : `php artisan migrate`, `php artisan test`, la page en local (mot de passe PostgreSQL inconnu sur ce PC). Lots 2 (CI) et 3 (déploiement) pas commencés. Le workflow `.github/workflows/tests.yml` du kit a été mis de côté, à refaire au lot 2.
