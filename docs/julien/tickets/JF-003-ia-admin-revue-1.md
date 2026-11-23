---
id: JF-003
titre: Service IA, journal des appels, quotas, erreurs IA et tableau de bord admin (revue n°1)
statut: en cours           # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/jf-003-roles, puis feature/jf-003-ia, puis feature/jf-003-ecrans (une PR par lot)
créé: 2026-11-23
mis-à-jour: 2026-11-23
---
## Contexte

La revue n°1 du 24 novembre attend « tableau de bord administrateur, quotas, expérience d'erreur » (objectifs O4 et O5 de l'énoncé), avec une démo en production et une capture du tableau de bord des coûts dans le rapport d'avancement. C'est la partie de Julien dans `AGENTS.md`. Le cœur du jeu (O1) suit après la revue : il a besoin du schéma de données écrit avec Florentin. Pour avoir un vrai appel au modèle à montrer, une première fonction simple sert de démo : l'« atelier promo » (le joueur décrit un catcheur fictif, le modèle écrit sa promo).

## Découpage

- **Lot 1, rôles** (`feature/jf-003-roles`) : colonnes `role` (`user` ou `admin`) et `tier` (palier, `free` par défaut) sur `users`, garde `admin` côté serveur, commande `php artisan app:make-admin <email>`, `isAdmin` partagé avec le front, lien « Administration » visible des seuls admins. Minimal : les comptes et rôles (O3) restent la partie de Florentin.
- **Lot 2, service IA** (`feature/jf-003-ia`) : `app/Ai/` avec une interface, le fournisseur OpenRouter (clé seulement sur le serveur, `zdr` et `data_collection: deny`, délai de 20 s) et un faux fournisseur ; les 4 erreurs d'O5 en exceptions distinctes ; table `ai_calls` (date, utilisateur, fonction, modèle, jetons entrée et sortie, coût, durée, succès, type d'erreur) ; quota d'appels par jour selon le palier, vérifié avant l'appel ; réglage admin du fournisseur (réel, faux, ou faux qui provoque une des 4 erreurs) ; décision D-003 sur le choix du modèle.
- **Lot 3, écrans** (`feature/jf-003-ecrans`) : page joueur « Atelier promo » (saisie, réponse marquée « Générée par IA », quota restant, messages d'erreur en français, saisie conservée) ; tableau de bord admin des coûts (totaux par jour et par utilisateur, derniers appels, taux d'échec) ; page admin des réglages IA et du palier d'un utilisateur ; tout utilisable sur téléphone.
- **Après les lots** : clé OpenRouter mise par Julien dans le `.env` de production, Julien nommé admin en production, captures et rapport d'avancement.

## Critères d'acceptation

- [x] Un utilisateur qui n'est pas admin reçoit 403 sur chaque route admin (vérifié par des tests, pas seulement par l'écran).
- [ ] Chaque appel au modèle, réussi ou non, crée une ligne dans `ai_calls` avec date, utilisateur, modèle, jetons, coût, durée et succès.
- [ ] Le quota du jour est vérifié avant l'appel : au-delà, aucun appel n'est fait et un message en français s'affiche.
- [ ] Les 4 erreurs (délai, panne, crédit épuisé, réponse inexploitable) se provoquent depuis le réglage admin et affichent chacune leur message, sans stack trace, saisie conservée.
- [ ] Le tableau de bord admin montre les totaux de coût et d'appels par jour et par utilisateur.
- [x] Les tests n'appellent jamais le vrai modèle (faux fournisseur, `Http::fake`).
- [ ] La clé OpenRouter n'est ni dans le dépôt ni dans le navigateur.
- [ ] En production : un vrai appel réussi apparaît dans le tableau de bord avec son coût.

## Journal du ticket

- 2026-11-23 : créé, découpage proposé à Julien.
- 2026-11-23 : lot 1 (rôles) fait par un sous-agent, relu et vérifié : colonnes `role` et `tier` (enums, non modifiables par formulaire), Gate `admin`, `routes/admin.php` (`auth` + `can:admin`), page `admin/Index`, commande `app:make-admin`, lien « Administration ». 12 tests ajoutés ; `composer ci:check` vert (66 tests), build OK ; essai réel : page admin visible pour un admin, 403 et lien caché après `--revoke`.
- 2026-11-23 : lot 2 (service IA) fait par un sous-agent, relu et vérifié : `app/Ai/` (interface, OpenRouter avec `zdr`, faux fournisseur et ses 4 erreurs, `AiGateway`), tables `ai_calls` et `settings`, quota par palier avant l'appel, `Http::preventStrayRequests()` pour tous les tests. 33 tests ajoutés ; `composer ci:check` vert (99 tests), build OK. D-003 (Mistral Small 3.2, clé plafonnée à 0,50 €) et `references/ia.md` écrits. Non vérifié : un vrai appel OpenRouter (format de `usage.cost`, acceptation de `zdr`), à voir en production.
