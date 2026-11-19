# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

Le squelette (JF-001 lot 1) et JF-002 sont dans `main`. Le lot 2 (CI GitHub Actions, job `ci`) est en PR #3 sur `feature/ci` et passe au vert. La protection de `main` exige une PR mais pas d'approbation (choix de Julien) ni, pour l'instant, le check `ci`.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lot 2 en PR #3, lot 3 à faire) |

## Prochaine action

1. Base locale : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. Rendre le check `ci` obligatoire dans la protection de `main` (Settings → Branches), puis fusionner la PR #3.
3. JF-001 lot 3 : déploiement sur le VPS (cohabitation avec ChatPulse et nom de domaine à décider), sur une nouvelle branche partie de `main`.
4. Plus tard : avertissements Vue « Hydration mismatch » sur `AppLayout` (kit), et aligner `"php": "^8.3"` de `composer.json` sur PHP 8.4.
