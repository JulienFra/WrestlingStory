# État de Florentin, mis à jour le 2026-11-24

## Où j'en suis

Le poste est installé (PHP 8.4.28, Composer, gh connecté). Brainstorm avec Julien : je fais fédérations, catcheurs, titres, rivalités et le dashboard de la fédération ; Julien fait le main event et la narration IA. FL-001 (schéma) est codé sur `feature/fl-001-schema`, pas encore commité : 11 tests du domaine verts, suite complète à relancer `composer dev` arrêté.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| FL-001 | Schéma du domaine | en cours |
| FL-002 | Fédérations, limite free 1 / pro 10 | ouvert |
| FL-003 | Roster (créateur de catcheur manuel) | ouvert |
| FL-004 | Titres (champion) et rivalités | ouvert |
| FL-005 | Dashboard de la fédération | ouvert |

## Prochaine action

1. Arrêter `composer dev`, puis `npm run build` et `composer ci:check` : la suite complète doit être verte.
2. Commit sur `feature/fl-001-schema` et PR ; demander à Julien s'il ajoute ses tables (shows, segments, matchs) dans cette PR et quelles colonnes son moteur attend.
3. Ensuite FL-002 (nouvelle branche depuis `main` après la fusion de FL-001).
