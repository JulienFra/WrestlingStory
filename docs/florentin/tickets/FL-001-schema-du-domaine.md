---
id: FL-001
titre: Schéma du domaine (fédérations, catcheurs, titres, règnes, rivalités)
statut: terminé           # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/fl-001-schema
créé: 2026-11-24
mis-à-jour: 2026-11-24
---
## Contexte

Exigence O2 : au moins quatre entités métier reliées, avec intégrité, validation et données de démo. Brainstorm du 2026-11-24 avec Julien : Florentin crée les fédérations, catcheurs, titres et rivalités ; Julien construit le main event et la narration IA (O1) dessus, avec ses propres tables (shows, segments, matchs). `AGENTS.md` dit que le schéma s'écrit à deux, en une PR : cette PR est ouverte par Florentin, Julien y ajoute ou relit ses tables (à confirmer avec lui).

## Données

- `federations` : `user_id` (suppression en cascade), `name` (unique par joueur), `description` facultative.
- `wrestlers` : `federation_id`, `ring_name` (unique dans la fédération), `gimmick`, `alignment` (`face` ou `heel`), `power`, `technique`, `charisma`, `stamina`, `popularity` (entiers 0 à 100), `status` (`active` ou `injured`).
- `titles` : `federation_id`, `name` (unique dans la fédération), `prestige` (0 à 100).
- `title_reigns` : `title_id`, `wrestler_id`, `started_at`, `ended_at` facultatif ; au plus un règne ouvert par titre.
- `rivalries` : `federation_id`, `wrestler_a_id`, `wrestler_b_id` (différents), `intensity` (0 à 100), `status` (`active` ou `ended`).

## Critères d'acceptation

- [x] Les 5 tables existent avec leurs clés étrangères, et `php artisan migrate:fresh --seed` passe sur PostgreSQL.
- [x] La base refuse une stat hors de 0 à 100, une rivalité d'un catcheur contre lui-même et un second règne ouvert sur le même titre (vérifié par des tests).
- [x] Supprimer un utilisateur supprime ses fédérations et tout leur contenu.
- [x] Chaque modèle a ses relations et sa factory ; le seeder crée une fédération de démo entièrement fictive (aucun vrai catcheur ni vraie marque).
- [x] `npm run build` puis `composer ci:check` sont verts.
- [x] D-004 : la table `main_events` existe avec ses contraintes (testées).
- [x] D-004 : une rivalité a un titre facultatif (`title_id`) et un catcheur n'a qu'une rivalité en cours, refusé par la base même sur un double clic (testé).

## Questions ouvertes (avec Julien)

- Julien ajoute-t-il ses tables (shows, segments, matchs) dans cette même PR ?
- Quelles colonnes son moteur attend-il en plus (argent, audience de la fédération, moral d'un catcheur…) ?

## Journal du ticket

- 2026-11-24 : créé, découpage validé par Florentin.
- 2026-11-24 : lot 1 fait par un sous-agent (5 migrations, 3 enums, 5 modèles, factories, `DemoFederationSeeder`, 11 tests dans `tests/Feature/Domain/`), relu. Revérifié par l'agent principal : 11 tests du domaine verts, Larastan 0 erreur, Pint vert. Le sous-agent rapporte `migrate:fresh --seed` réussi en local (pas revu : il efface la base locale). Suite complète pas encore verte en local : 21 tests de pages échouent tant que `composer dev` tourne (`public/hot`), à relancer serveur arrêté.
- 2026-11-24 : `main` (D-004) fusionnée dans la branche. Schéma adapté : `rivalries.title_id` facultatif (null si le titre est supprimé) et contrainte d'exclusion (extension `intarray`) : un catcheur n'a qu'une rivalité active, de quelque côté qu'il soit. 3 tests en plus. `public/hot` resté après l'arrêt de `composer dev`, supprimé. Vérifié : `npm run build` OK, `composer ci:check` vert (Pint, oxlint, vue-tsc, Larastan 0 erreur, 136 tests passés et 3 ignorés sur 139), `migrate:fresh --seed` OK sur la base de test puis sur la base locale. `main_events` reste à ajouter par Julien (D-004).
- 2026-11-24 : Julien demandait qui fait la DB ; Florentin a choisi d'ajouter `main_events` lui-même. Table selon D-004 : type simple ou rivalité, 2 catcheurs, titre et vainqueur imposé facultatifs, date de sortie, statut prévu ou joué, vainqueur, récit, suite logique, date de jeu. La base vérifie la forme (vainqueur parmi les 2 catcheurs, un match joué a son récit, un match de rivalité a sa rivalité) ; le reste (même fédération, date, champion dans un match de titre) est au code de Julien. Démo : un match de titre prévu samedi, un match joué. Vérifié : `composer ci:check` vert (144 tests passés et 3 ignorés sur 147), `migrate:fresh --seed` OK sur la base de test.
- 2026-11-24 : PR #15 fusionnée par Julien. Terminé.
