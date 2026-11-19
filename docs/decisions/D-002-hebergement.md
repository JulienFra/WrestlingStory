---
id: D-002
titre: WrestlingStory sur le VPS HOSTKEY à côté de ChatPulse, sans nom de domaine (sslip.io + HTTPS)
statut: proposée          # proposée | acceptée | remplacée par D-00N
proposée-par: julien
date: 2026-11-19
---
## Contexte

D-001 fixe l'hébergement : le VPS HOSTKEY du projet 1, avec Nginx et PHP-FPM, déployé par GitHub Actions en SSH. Ce VPS fait déjà tourner ChatPulse : Nginx sur le port 80 (site par défaut, sur l'IP nue, en HTTP), le backend Node avec pm2 sur le port 3000, et PostgreSQL 16 sous Docker sur 127.0.0.1:5432. La machine a 2 CPU et 1,8 Go de RAM, sans swap. Les deux projets doivent tourner en même temps. Le groupe n'a pas de nom de domaine, et les passkeys du starter kit exigent HTTPS.

## Décision

- **Adresse** : `https://wrestlingstory.82-25-58-90.sslip.io`. sslip.io est un DNS public et gratuit : tout nom qui contient l'IP pointe vers cette IP, sans inscription. Le certificat vient de Let's Encrypt (certbot) et HTTP redirige vers HTTPS.
- **Cohabitation** : Nginx sépare les deux sites par leur nom. ChatPulse garde le site par défaut et n'est pas modifié.
- **PHP** : PHP 8.4-FPM (dépôt `ppa:ondrej/php`), avec un pool `wrestlingstory` qui tourne sous l'utilisateur `deploy` (socket `/run/php/wrestlingstory.sock`).
- **Base** : un PostgreSQL 17 séparé sous Docker, `wrestlingstory_postgres`, sur `127.0.0.1:5433`, comme en local et en CI.
- **Fichiers** : `/var/www/wrestlingstory/` contient `releases/<date>-<sha>/`, `shared/.env`, `shared/storage/`, et `current`, un lien vers la version en ligne.
- **Déploiement** : `.github/workflows/deploy.yml` se lance après une CI verte sur `main`. Tout est construit sur GitHub (Composer sans les outils de dev, Vite). L'archive est envoyée en SSH avec une clé dédiée (secret `VPS_SSH_KEY`), puis `deploy/release.sh` lance les migrations, met la config en cache, bascule `current`, recharge PHP-FPM et garde les 3 dernières versions. Le workflow vérifie enfin que la page d'accueil répond 200.
- **Installation unique** : `deploy/setup-vps.sh`, lancé par Julien (sudo demande son mot de passe). Il crée aussi un swap de 2 Go et autorise `deploy` à recharger PHP-FPM, et rien d'autre, sans mot de passe.
- **Production** : `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=warning`, `INERTIA_SSR_ENABLED=false`, en-têtes de sécurité dans Nginx (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS).

## Écarté

- **Acheter un domaine** : coût, et rien ne presse pour le projet. Le jour où on en a un, il suffit de changer le `server_name` dans Nginx et `APP_URL`, puis de relancer certbot.
- **Un autre port sur l'IP** (`http://82.25.58.90:8080`) : pas de certificat possible facilement, donc pas de HTTPS. Les passkeys et les cookies sécurisés ne marchent pas.
- **Un sous-chemin** (`/wrestling`) sous ChatPulse : fragile avec Laravel, Vite et Inertia, et il mélange les deux projets dans un même site Nginx.
- **Partager le PostgreSQL 16 de ChatPulse** : la version diffère de celle de D-001, et une erreur sur un projet toucherait l'autre.
- **Construire sur le VPS** (`composer install`, `npm run build`) : 1,8 Go de RAM partagés avec ChatPulse, et des outils de build inutiles en production.
- **Le SSR d'Inertia en production** : il demanderait un processus Node de plus sur une petite machine. Le rendu se fait dans le navigateur.

## Conséquences

- Le premier déploiement suppose : `deploy/setup-vps.sh` lancé par Julien, le port 443 ouvert (ufw et pare-feu HOSTKEY), et le secret `VPS_SSH_KEY` sur GitHub.
- Le `.env` de production n'existe que sur le VPS (`shared/.env`). Ses secrets sont générés sur place et jamais affichés. La clé OpenRouter y sera ajoutée à la main.
- `sslip.io` est un service tiers : s'il tombe, l'adresse ne répond plus, alors que le site tourne toujours. La sortie de secours est l'IP, ou un vrai domaine.
- Le changement de version est atomique et l'ancienne version reste disponible. Revenir en arrière : faire pointer `current` vers la version précédente, puis recharger PHP-FPM.
