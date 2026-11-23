# État de Julien, mis à jour le 2026-11-23

## Où j'en suis

WrestlingStory est en ligne sur https://wrestlingstory.82-25-58-90.sslip.io (HTTPS), à côté de ChatPulse sur le même VPS. Chaque fusion dans `main` passe les 4 checks (`Lint`, `Types`, `Tests`, `Build`) puis se déploie toute seule (workflow « Deploy »). JF-001 est terminé (dernière case vérifiée dans un clone neuf). D-001 et D-002 sont acceptées.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-003 | Service IA, journal, quotas, erreurs, tableau de bord admin (revue n°1) | en cours (lots 1 à 3 en PR, mise en production à faire) |

## Prochaine action

1. Base locale : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. JF-003 pour la revue du 24 novembre : lot 2 (service IA, journal, quotas, D-003), lot 3 (atelier promo, tableau de bord admin), puis clé OpenRouter et admin en production (Julien), captures et rapport d'avancement. Ensuite : schéma de données avec Florentin, puis O1.
3. Sécurité, plus tard : throttle sur l'inscription et la confirmation du mot de passe, désactiver « mot de passe oublié » tant qu'il n'y a pas de mail.
4. Plus tard : « Hydration mismatch » sur `AppLayout` (kit), `"php": "^8.3"` à aligner sur 8.4.
