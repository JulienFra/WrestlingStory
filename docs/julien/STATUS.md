# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

PR #1 (kit agents) est fusionnée dans `main`. Sur le PC portable, le lot 1 de JF-001 tourne : PostgreSQL 17 dans Docker (port 5433), migrations passées, `composer ci:check` vert, build OK, page d'accueil vue en local. JF-002 (pseudo unique, e-mail valide, robuste à F12) est fait sur la même branche. Le hash de `composer.lock` est corrigé. `composer dev` tourne et le parcours réel est fait. Rien n'est commité.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lot 1 prêt pour la PR) |
| JF-002 | Pseudo unique et e-mail valide, vérifiés par le serveur | en cours (fait, à faire relire) |

## Prochaine action

1. Base : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. Commiter le lot 1 sur `feature/squelette-laravel` et ouvrir sa PR vers `main` (elle contient aussi le passage à Docker et JF-002, qui touche aux comptes de Florentin : à lui signaler).
3. Faire relire `D-001-stack.md` et les règles du skill `stack-laravel-vue`.
4. JF-001 lot 2 : CI GitHub Actions (PostgreSQL en service du job), puis l'ajouter à la protection de `main`. Lot 3 : déploiement sur le VPS (cohabitation avec ChatPulse et nom de domaine à décider).
5. Plus tard : avertissements Vue « Hydration mismatch » sur `AppLayout` (kit), et aligner `"php": "^8.3"` de `composer.json` sur PHP 8.4.
