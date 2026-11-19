# État de Julien, mis à jour le 2026-11-19

## Où j'en suis

Le kit agents est en PR #1 (`setup/agents-md` → `main`), qui attend l'approbation de Florentin ; `main` est protégée. Le squelette Laravel 13 + Vue/Inertia est sur `feature/squelette-laravel` : lint, analyse statique et build passent, mais migrations, tests et page locale ne sont pas vérifiés. La suite se fait sur le PC portable, qui n'a peut-être ni PHP, ni Composer, ni PostgreSQL.

## Tickets ouverts

| Ticket | Sujet | Statut |
| --- | --- | --- |
| JF-001 | Squelette Laravel + Vue/Inertia déployé sur le VPS | en cours (lot 1 presque fini) |

## Prochaine action

1. Sur le PC portable : `git clone https://github.com/JulienFra/WrestlingStory.git`, puis `git switch feature/squelette-laravel`.
2. Installer ce qui manque : PHP 8.4 (`winget install PHP.PHP.8.4`, puis un `php.ini` avec pgsql, pdo_pgsql, mbstring, openssl, intl, curl, fileinfo, zip, sodium et `memory_limit = 512M`), Composer, PostgreSQL 17 (mot de passe `postgres` à noter).
3. `composer setup`, puis remplir `DB_PASSWORD` dans `.env` et créer les bases `wrestlingstory` et `wrestlingstory_test` (`createdb -U postgres …`).
4. Lancer `php artisan migrate`, `php artisan test`, `composer dev` et regarder la page d'accueil : finir le lot 1 de JF-001, puis ouvrir sa PR.
5. Quand Florentin a approuvé PR #1 : la fusionner. Lui faire relire `D-001-stack.md` et les règles du skill `stack-laravel-vue`.
6. JF-001 lot 2 : CI GitHub Actions (s'inspirer du workflow du starter kit), puis l'ajouter à la protection de `main`. Lot 3 : déploiement sur le VPS (cohabitation avec ChatPulse et nom de domaine à décider).
