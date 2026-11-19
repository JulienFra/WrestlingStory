# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

WrestlingStory est en ligne sur https://wrestlingstory.82-25-58-90.sslip.io (HTTPS), à côté de ChatPulse sur le même VPS. Chaque fusion dans `main` passe les 4 checks (`Lint`, `Types`, `Tests`, `Build`) puis se déploie toute seule (workflow « Deploy »). JF-001 n'attend plus qu'une case : `composer setup` lancé d'un seul tenant.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lots 1 à 3 faits, une case restante) |

## Prochaine action

1. Base locale : Docker Desktop lancé, puis `docker compose up -d` (le `.env` existe, `DB_PORT=5433`).
2. Clore JF-001 : lancer `composer setup` d'un seul tenant (avec `COMPOSER_PROCESS_TIMEOUT=0`), puis passer le ticket à `terminé`.
3. Faire accepter D-002 (hébergement) par Florentin.
4. Préparer la revue du 24 novembre, puis la suite : schéma de données à deux, puis service IA (OpenRouter, faux fournisseur, journal des appels).
5. Sécurité, plus tard : throttle sur l'inscription et la confirmation du mot de passe, désactiver « mot de passe oublié » tant qu'il n'y a pas de mail.
6. Plus tard : « Hydration mismatch » sur `AppLayout` (kit), `"php": "^8.3"` à aligner sur 8.4.
