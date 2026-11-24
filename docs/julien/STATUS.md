# État de Julien, mis à jour le 2026-11-24

## Où j'en suis

WrestlingStory est en ligne sur https://wrestlingstory.82-25-58-90.sslip.io (HTTPS), à côté de ChatPulse sur le même VPS. Chaque fusion dans `main` passe les 4 checks (`Lint`, `Types`, `Tests`, `Build`) puis se déploie toute seule (workflow « Deploy »). JF-001, JF-002 et JF-003 sont terminés : service IA (OpenRouter, Mistral Small 3.2), journal des appels, quotas par palier, 4 erreurs simulables, atelier promo et tableau de bord admin des coûts, en production. D-001, D-002 et D-004 (règles du jeu) acceptées, D-003 proposée. Florentin travaille désormais avec son agent (PR #13).

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| — | aucun ticket ouvert | prochain : schéma de données (à deux, selon D-004), puis le main event (O1) |

## Prochaine action

1. Base locale : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. Revue n°1 (24 novembre) : le rapport PDF est dans `docs/julien/revues/2026-11-24/` ; à noter ici les retours du professeur.
3. Schéma de données avec Florentin selon D-004 (`federations`, `wrestlers`, `titles`, `title_reigns`, `rivalries`, `main_events`), en une PR à deux. Ensuite : le main event (O1) et le lien public de la fédération (répartition dans `AGENTS.md`).
4. À trancher : les appels en échec comptent-ils dans le quota ? (aujourd'hui oui).
5. Sécurité, plus tard : throttle sur l'inscription et la confirmation du mot de passe, désactiver « mot de passe oublié » tant qu'il n'y a pas de mail.
6. Plus tard : « Hydration mismatch » sur `AppLayout` (kit), `"php": "^8.3"` à aligner sur 8.4.
