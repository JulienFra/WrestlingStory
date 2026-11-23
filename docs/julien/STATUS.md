# État de Julien, mis à jour le 2026-11-23

## Où j'en suis

WrestlingStory est en ligne sur https://wrestlingstory.82-25-58-90.sslip.io (HTTPS), à côté de ChatPulse sur le même VPS. Chaque fusion dans `main` passe les 4 checks (`Lint`, `Types`, `Tests`, `Build`) puis se déploie toute seule (workflow « Deploy »). JF-001, JF-002 et JF-003 sont terminés : service IA (OpenRouter, Mistral Small 3.2), journal des appels, quotas par palier, 4 erreurs simulables, atelier promo et tableau de bord admin des coûts, en production. D-001 et D-002 acceptées, D-003 proposée.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| — | aucun ticket ouvert | prochain : schéma de données (à deux), puis O1 |

## Prochaine action

1. Base locale : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. Revue n°1 (24 novembre) : déposer le rapport d'avancement avec captures (dont le tableau de bord des coûts) dans le devoir Teams ; démo en production (atelier promo, 4 erreurs via Réglages IA, tableau de bord) ; point fort, point faible, objectif.
3. Ensuite : schéma de données avec Florentin (au moins 4 entités reliées, O2), puis le booking d'un segment (O1).
4. À trancher : les appels en échec comptent-ils dans le quota ? (aujourd'hui oui).
5. Sécurité, plus tard : throttle sur l'inscription et la confirmation du mot de passe, désactiver « mot de passe oublié » tant qu'il n'y a pas de mail.
6. Plus tard : « Hydration mismatch » sur `AppLayout` (kit), `"php": "^8.3"` à aligner sur 8.4.
