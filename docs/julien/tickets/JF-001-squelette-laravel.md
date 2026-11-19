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

- [x] Le projet est créé avec le starter kit officiel Laravel + Vue, et `composer run dev` affiche la page d'accueil en local.
- [x] L'application utilise PostgreSQL et `php artisan migrate` passe sur une base vide.
- [ ] Les commandes de la table « Commandes » de `AGENTS.md` sont confirmées (lancées une fois chacune) ou corrigées.
- [x] Une PR vers `main` déclenche la CI : lint (Pint, oxlint), types (Larastan, vue-tsc), tests (`php artisan test`) et build (`npm run build`) ; un job rouge bloque le merge.
- [ ] Un merge sur `main` déploie automatiquement sur le VPS (PHP-FPM derrière Nginx, migrations appliquées).
- [ ] La page d'accueil répond en production, à l'adresse publique du VPS.
- [ ] Aucun secret dans le dépôt : `.env` ignoré par git, secrets du déploiement dans les GitHub Secrets.

## Journal du ticket

- 2026-11-19 : créé. Idée, stack et hébergeur validés par le professeur.
- 2026-11-19 : lot 1 (squelette local) presque fini sur le PC fixe. Laravel 13.43 + Vue/Inertia 3 créé à la racine, PostgreSQL configuré (`wrestlingstory`, `wrestlingstory_test`). Vérifiés : `composer lint:check`, `composer types:check`, `npm run check`, `npm run types:check`, `npm run build`. Non vérifiés : `php artisan migrate`, `php artisan test`, la page en local (mot de passe PostgreSQL inconnu sur ce PC). Lots 2 (CI) et 3 (déploiement) pas commencés. Le workflow `.github/workflows/tests.yml` du kit a été mis de côté, à refaire au lot 2.
- 2026-11-19 (soir) : reprise sur le PC portable. PHP 8.4.27 et Composer 2.11.2 installés. PostgreSQL local passe par Docker Desktop : `compose.yaml` + `docker/postgres/init.sql`, ordre d'installation corrigé dans `AGENTS.md` (`composer setup` lance `migrate`, la base doit tourner avant). Vérifié : `docker compose config` valide, et refuse de démarrer sans `DB_PASSWORD`. Non vérifié : conteneur démarré, `migrate`, tests, page locale (`.env` à remplir par Julien).
- 2026-11-19 (soir, suite) : à la demande de Julien, l'agent a créé `.env` depuis `.env.example` avec un `DB_PASSWORD` aléatoire, jamais affiché. `DB_PORT=5433`, parce que 5432 est pris par `chatapp_postgres`. Conteneur `healthy`, bases `wrestlingstory` et `wrestlingstory_test` présentes. Vérifiés : `php artisan migrate` (4 migrations), `composer ci:check` (Pint, oxlint et format, Larastan 0 erreur, vue-tsc, PHPUnit 27 réussis et 3 ignorés car fonctions Fortify désactivées, 76 assertions), `npm run build`, page d'accueil 200 sans erreur console via `php artisan serve`. Non vérifiés : `composer dev` lui-même ; `composer setup` d'un bout à l'autre (délai Composer de 300 s dépassé, étapes finies à la main).
- 2026-11-19 (nuit) : `composer dev` essayé : serveur sur 8000, Vite sur 5173, file d'attente active, sans erreur au démarrage. Parcours réel dans le navigateur : inscription `Demo_Booker`, tableau de bord, profil, déconnexion, connexion. Avertissements Vue « Hydration mismatch » (SSR) sur `AppLayout`, aussi sur le tableau de bord non modifié : vient du kit, à regarder plus tard. Toutes les commandes de la table « Commandes » ont tourné au moins une fois ; `composer setup` seulement par étapes (délai de 300 s).
- 2026-11-19 (nuit) : lot 2 sur `feature/ci`, PR #3. `.github/workflows/ci.yml` : job `ci`, PostgreSQL 17 en service, PHP 8.4, Node 24, `npm run build` puis `composer ci:check`. Premier passage rouge : `npm ci` refusait `package-lock.json` désynchronisé (react@19.3.1 manquant) ; lock régénéré et commité. Deuxième passage vert en 1 min 41 (oxlint, format, vue-tsc, Pint, Larastan, PHPUnit). Reste : rendre le check `ci` obligatoire dans la protection de `main` pour que le critère « un job rouge bloque le merge » soit vrai.
- 2026-11-19 (nuit) : check `ci` rendu obligatoire dans la protection de `main` (branche à jour exigée, admins inclus, pas d'approbation), vérifié dans les réglages GitHub. Lot 2 terminé, fusionné avec la PR #3.
