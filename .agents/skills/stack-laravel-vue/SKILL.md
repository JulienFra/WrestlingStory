---
name: stack-laravel-vue
description: La stack de WrestlingStory (Laravel 13, Vue 3 via Inertia, PostgreSQL 17, OpenRouter) et la façon dont le groupe veut qu'on y code. À charger avant d'écrire du code ou un test, et pour « ajoute un écran », « écris une route », « ajoute une migration », « appelle le modèle », « lance les vérifications », « pourquoi ce test est rouge », « pourquoi la CI échoue ».
---

# Stack du groupe : Laravel + Vue/Inertia, PostgreSQL, VPS HOSTKEY

Ce skill décrit la stack de WrestlingStory et la façon dont le groupe veut qu'on y travaille. Lis-le en entier avant d'écrire du code. Florentin le lit aussi, sans agent : il doit rester clair pour un humain.

## Avant de toucher au code

1. Lis `AGENTS.md` et les décisions de `docs/decisions/`. Le dépôt prime sur ce skill : s'ils se contredisent, signale-le.
2. Lis les versions réelles dans `composer.json`/`composer.lock` et `package.json`/`package-lock.json`. Ne les suppose pas.
3. Trouve un fichier voisin qui fait déjà ce que tu dois faire, et copie sa forme (un contrôleur dans `app/Http/Controllers/Settings/`, une page dans `resources/js/pages/settings/`, un test dans `tests/Feature/`).

## La stack en une table

| Couche | Choix | Remarque |
| --- | --- | --- |
| Back | Laravel 13, PHP 8.4 | créé avec le starter kit officiel `laravel/vue-starter-kit` (branche main), le 2026-11-19 |
| Auth | Fortify (inscription, connexion, confirmation du mot de passe, passkeys) | pas de vérification d'e-mail ni de 2FA : pas de serveur mail |
| Front | Vue 3 + TypeScript, Inertia 3, Tailwind 4, composants shadcn-vue dans `resources/js/components/ui/` | Wayfinder génère les routes typées dans `resources/js/routes/` et `resources/js/actions/` : ne les édite pas |
| Outils front | Vite+ (`vp`) : build, oxlint, formatage | config dans `vite.config.ts` |
| Base | PostgreSQL 17 | en local dans Docker Desktop (`compose.yaml`, identifiants lus dans `.env`) ; `wrestlingstory` en local, `wrestlingstory_test` pour les tests (créée par `docker/postgres/init.sql`) |
| Modèle de langage | OpenRouter, appelé seulement par le serveur | clé dans `.env` (`OPENROUTER_API_KEY`), jamais dans le code ; plafond 1 € pour tout le projet ; modèle à choisir et justifier |
| Qualité | Pint, Larastan niveau 7, oxlint, vue-tsc, PHPUnit 12 | |
| CI | GitHub Actions (`.github/workflows/ci.yml`) | job `ci` : PostgreSQL 17 en service, PHP 8.4, Node 24, `npm run build` puis `composer ci:check` ; check requis pour fusionner dans `main` |
| Production | VPS HOSTKEY : Nginx, PHP-FPM, PostgreSQL | déploiement par GitHub Actions en SSH (à faire, JF-001 lot 3) |

## Nos règles (le cœur)

> Proposées par Julien et acceptées par Florentin le 2026-11-19. Une règle se change par PR.

- **Où va chaque chose** :
  - validation des entrées : une `FormRequest` dans `app/Http/Requests/`, jamais dans le contrôleur ;
  - droits (« cette fédération est-elle à moi ? ») : une `Policy` par modèle, appelée dans le contrôleur ; le serveur vérifie toujours, l'écran ne suffit pas ;
  - règle du jeu (appliquer un effet, calculer la note d'un show) : une classe dans `app/Game/`, sans HTTP ni appel au modèle, testable seule ;
  - appel au modèle : uniquement dans `app/Ai/`, derrière une interface ; un faux fournisseur la remplace dans les tests.
- **La sortie du modèle est une entrée non fiable** : on la valide contre un schéma, on vérifie que chaque catcheur, titre ou rivalité cité existe dans la fédération de l'utilisateur, on borne chaque delta, et on refuse le reste. Le modèle ne déclenche jamais une action sans que le code la vérifie.
- **Chaque appel au modèle passe par le journal** (date, utilisateur, modèle, jetons, coût, durée, succès) et par le contrôle de quota, avant l'appel.
- **Les noms** : classes et méthodes en anglais (`WrestlerController`, `ApplySegmentEffects`), tables au pluriel (`wrestlers`, `title_reigns`), pages Vue en PascalCase (`pages/roster/Index.vue`), tests qui disent la règle (`test_player_cannot_book_injured_wrestler`).
- **La taille** : une méthode tient sur un écran (40 lignes) ; un composant Vue au-delà de 200 lignes se découpe.
- **La langue** : code en anglais, textes affichés en français.
- **Le style** : celui de la documentation Laravel et du starter kit ; pas d'abstraction tant qu'un seul endroit s'en sert.
- **Les erreurs** : l'utilisateur voit un message en français et garde sa saisie ; jamais de stack trace (`APP_DEBUG=false` en production) ; les quatre cas d'erreur IA (délai, panne, crédit épuisé, réponse inexploitable) ont chacun leur message.
- **Les migrations** : une migration fusionnée ne se modifie plus, on en ajoute une nouvelle.
- **Ce qu'on ne fait pas** :
  - pas d'appel réel au modèle dans les tests : il coûte et il varie ;
  - pas de vrai nom de la WWE ni de vrai catcheur, même dans les seeders ;
  - pas de secret dans le dépôt : `.env` reste local et ignoré par git.

## Les vérifications, dans l'ordre

| # | Commande | Ce qu'elle attrape |
| --- | --- | --- |
| 1 | `composer lint:check` puis `npm run check` | style PHP (Pint), lint et formatage du front (oxlint) |
| 2 | `composer types:check` puis `npm run types:check` | erreurs de types PHP (Larastan) et Vue/TypeScript (vue-tsc) |
| 3 | `php artisan test` | règles du jeu, droits, validation, pages qui répondent |
| 4 | `npm run build` | un import cassé, une page qui ne compile pas |
| 5 | l'essai réel : l'écran sur téléphone, un vrai appel au modèle avec son coût noté | ce que les tests ne voient pas |

`composer ci:check` enchaîne 1 à 3 ; la CI lance `npm run build` puis `composer ci:check`. Avant de dire « fini », lance-les et montre les sorties réelles. Dis ce que tu n'as pas vérifié.

## Pièges déjà payés

- **`composer create-project laravel/vue-starter-kit` installe Laravel 12 et Tailwind 3** : le dernier tag publié du kit est ancien. Utiliser la branche main (`laravel/vue-starter-kit:dev-main`), comme le fait l'installeur `laravel new`.
- **Retirer les passkeys du starter kit casse le build** (`PasskeyVerify.vue` introuvable) : la suppression est incomplète. On garde les passkeys.
- **PHPStan s'arrête sur « reached configured PHP memory limit: 128M »** : `php.ini` par défaut. Mettre `memory_limit = 512M` dans le `php.ini` local.
- **`npm run check` signale des fichiers Markdown** : le formateur lisait la documentation. `docs/`, `consignes/` et `*.md` sont ignorés dans `vite.config.ts`.
- **`composer setup` échoue sur `migrate`** : la base ne tourne pas encore. Lancer `docker compose up -d` avant, avec `DB_PASSWORD` déjà rempli dans `.env`.
- **`docker compose up -d` échoue sur « port is already allocated »** : un autre PostgreSQL occupe 5432 (chez Julien, le conteneur `chatapp_postgres` de ChatPulse). Mettre `DB_PORT=5433` dans `.env` : Compose et Laravel lisent la même variable.
- **`composer setup` s'arrête sur « exceeded the timeout of 300 seconds »** : premier `composer install` lent sous Windows. Relancer avec `COMPOSER_PROCESS_TIMEOUT=0`, ou finir les étapes à la main (`php artisan key:generate`, `php artisan migrate`, `npm install`, `npm run build`).
- **`npm run build` dit « 'php' n'est pas reconnu »** : Wayfinder appelle `php` ; le terminal a été ouvert avant l'installation de PHP. Rouvrir le terminal.
- **Changer `DB_PASSWORD` ne change pas le mot de passe de la base Docker** : il n'est lu qu'à la création du volume, comme `init.sql`. Pour repartir de zéro (données perdues) : `docker compose down -v`, puis `docker compose up -d`.
- **`php` ou `composer` introuvable juste après l'installation** : le PATH n'est relu qu'au démarrage. Fermer et rouvrir le terminal ou l'application.

## Quel fichier lire, et quand

> À remplir quand le skill grandit : un fichier par sujet dans `references/`, des modèles à copier dans `assets/`.

| Tu dois… | Lis |
| --- | --- |
| écrire un écran | une page voisine dans `resources/js/pages/` |
| appeler le modèle | `references/ia.md` (à écrire avec le service IA) |
