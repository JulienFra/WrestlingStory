---
id: D-001
titre: Laravel + Vue 3 (Inertia) + PostgreSQL 17, hébergés sur le VPS HOSTKEY
statut: acceptée          # proposée | acceptée | remplacée par D-00N
proposée-par: julien
date: 2026-11-19
---
## Contexte

Le projet 2 impose de changer de framework backend et frontend par rapport au projet 1 (NestJS et React), avec un backend en langage interprété. Tous les appels au modèle passent par le serveur. Le choix doit être validé par le professeur avant la première ligne de code : il l'a été.

## Décision

- Backend : Laravel (PHP), version stable la plus récente, créé avec le starter kit officiel Vue.
- Frontend : Vue 3 en TypeScript, servi par Inertia (pas d'API REST séparée pour les écrans), Tailwind CSS.
- Base : PostgreSQL 17, la même en local, en CI et en production.
- Qualité : Pint (style PHP), Larastan (analyse statique), ESLint et vue-tsc côté front, tests avec `php artisan test`.
- Hébergement : le VPS HOSTKEY du projet 1 (Nginx, PHP-FPM, PostgreSQL), déployé par GitHub Actions en SSH.

## Écarté

- Livewire + Blade : très peu de JavaScript, mais une interface riche et le streaming des réponses du modèle sont plus difficiles ; le groupe connaît déjà le modèle composants de React, plus proche de Vue.
- Svelte + Inertia : pas de starter kit officiel Laravel, donc plus de mise en place pour l'authentification.
- Laravel Cloud ou un hébergeur gratuit : moins d'administration, mais coût à évaluer et risque de mise en veille pendant la démo ; le VPS est déjà maîtrisé.
- SQLite en local : plus simple à installer, mais différences de comportement avec PostgreSQL qui n'apparaîtraient qu'en CI ou en production.

## Conséquences

- Julien et Florentin installent PHP, Composer et PostgreSQL 17 sur leur poste.
- Le pipeline CI/CD du projet 1 est à refaire pour PHP (lint, analyse, tests sur une base PostgreSQL de test, build).
- Le VPS reçoit PHP-FPM ; la cohabitation avec ChatPulse (nom de domaine ou port) reste à décider.
- Le skill de stack du groupe (`stack-laravel-vue`) décrit ces choix et les commandes réelles.
