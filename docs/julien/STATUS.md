# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

Le squelette (JF-001 lot 1), JF-002 et la CI en 4 jobs obligatoires (`Lint`, `Types`, `Tests`, `Build`, lot 2) sont dans `main`. Le lot 3 (déploiement) est planifié, pas commencé : branche `feature/deploy` créée, rien de commité. VPS inspecté en lecture seule : Ubuntu 24.04, 1,8 Go de RAM sans swap, ChatPulse en HTTP (Nginx sur 80, pm2 sur 3000, PostgreSQL 16 Docker sur 127.0.0.1:5432), pas de PHP, sudo avec mot de passe.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lot 3 planifié) |

## Prochaine action

1. Lot 3 sur `feature/deploy` : adresse `wrestlingstory.82-25-58-90.sslip.io` en HTTPS (Let's Encrypt), ChatPulse inchangé.
2. Écrire `deploy/setup-vps.sh` (PHP 8.4-FPM, swap 2 Go, PostgreSQL 17 Docker sur 127.0.0.1:5433, site Nginx, certbot, sudoers limité au reload PHP-FPM, `/var/www/wrestlingstory` avec releases/shared/current). Julien le lance lui-même (`ssh -t deploy@82.25.58.90`) : sudo demande son mot de passe.
3. Vérifier que le port 443 est ouvert (ufw et pare-feu HOSTKEY).
4. `.github/workflows/deploy.yml` : build sur GitHub, envoi SSH avec une clé dédiée (Julien la met dans les GitHub Secrets), migrate, bascule `current`, reload PHP-FPM, test de la page d'accueil. Décision D-002 (hébergement sans domaine, sslip.io).
5. Audit sécurité : au lot 3, `.env` de production durci et en-têtes Nginx ; plus tard, throttle sur inscription et confirmation du mot de passe, désactiver « mot de passe oublié » sans mail.
6. Plus tard : « Hydration mismatch » sur `AppLayout` (kit), `"php": "^8.3"` à aligner sur 8.4, SSR désactivé en production.
