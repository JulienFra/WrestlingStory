# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

Le squelette Laravel 13 + Vue/Inertia (JF-001, lot 1) et JF-002 (pseudo unique, e-mail valide, robuste à F12) sont dans `main` via la PR #2. D-001 est acceptée. En local, PostgreSQL 17 tourne dans Docker (port 5433) et `composer ci:check` est vert. Il n'y a encore ni CI ni déploiement.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lot 1 fusionné, lots 2 et 3 à faire) |

## Prochaine action

1. Base : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. Partir de `main` à jour pour la tâche suivante, sur une nouvelle branche.
3. JF-001 lot 2 : CI GitHub Actions (PostgreSQL en service du job, lint, types, tests, build), puis l'ajouter à la protection de `main`. Vérifier cette protection : GitHub a laissé fusionner sans approbation.
4. JF-001 lot 3 : déploiement sur le VPS (cohabitation avec ChatPulse et nom de domaine à décider).
5. Plus tard : avertissements Vue « Hydration mismatch » sur `AppLayout` (kit), et aligner `"php": "^8.3"` de `composer.json` sur PHP 8.4.
