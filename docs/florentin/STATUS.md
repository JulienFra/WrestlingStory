# État de Florentin, mis à jour le 2026-11-24

## Où j'en suis

Le poste est installé (PHP 8.4.28, Composer, gh connecté). Brainstorm avec Julien : je fais fédérations, catcheurs, titres, rivalités et le dashboard de la fédération ; Julien fait le main event et la narration IA. D-004 (règles du jeu, Julien) est fusionnée et intégrée : FL-001 (schéma) est poussé sur `feature/fl-001-schema` avec sa PR, `composer ci:check` vert en local.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| FL-001 | Schéma du domaine | en cours |
| FL-002 | Fédérations, limite free 1 / pro 10 | ouvert |
| FL-003 | Roster (créateur de catcheur manuel) | ouvert |
| FL-004 | Titres (champion) et rivalités | ouvert |
| FL-005 | Dashboard de la fédération | ouvert |

## Prochaine action

1. Regarder la CI de la PR FL-001 ; Julien y ajoute `main_events` (D-004), relit et fusionne.
2. Ensuite FL-002 (nouvelle branche depuis `main` après la fusion de FL-001).
3. Le compte créé en local a été effacé par `migrate:fresh` : se réinscrire, puis `php artisan app:make-admin <email>`.
