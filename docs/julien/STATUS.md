# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

Le squelette (JF-001 lot 1), JF-002 et la CI GitHub Actions (lot 2, PR #3 puis 4 jobs en PR #4) sont dans `main`. La protection de `main` exige une PR et les 4 checks `Lint`, `Types`, `Tests`, `Build` verts sur une branche à jour, sans approbation (choix de Julien). Reste le lot 3 : le déploiement sur le VPS.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lots 1 et 2 fusionnés, lot 3 à faire) |

## Prochaine action

1. Base locale : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. JF-001 lot 3 : déploiement sur le VPS (cohabitation avec ChatPulse et nom de domaine à décider), sur une nouvelle branche partie de `main`.
3. Plus tard : avertissements Vue « Hydration mismatch » sur `AppLayout` (kit), et aligner `"php": "^8.3"` de `composer.json` sur PHP 8.4.
