---
id: FL-002
titre: Créer et lister ses fédérations, limite par palier
statut: en cours          # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/fl-002-federations
créé: 2026-11-24
mis-à-jour: 2026-11-24
---
## Contexte

Premier écran du jeu : le joueur crée sa fédération. Le palier limite leur nombre (free : 1, pro : 10), ce qui alimente le pricing (O6). Dépend de FL-001.

## Critères d'acceptation

- [x] Un joueur crée une fédération depuis un formulaire et la retrouve dans sa liste.
- [x] Un joueur free qui a déjà 1 fédération ne peut pas en créer une deuxième (refus côté serveur, message en français) ; un joueur pro est bloqué à 10.
- [x] Les limites sont dans la config (`config/game.php` ou équivalent), pas codées en dur dans le contrôleur.
- [x] Un joueur repassé en free garde ses fédérations mais n'en crée plus tant qu'il en a au moins une (D-004).
- [x] Supprimer une fédération demande une confirmation et supprime tout son contenu (D-004).
- [x] Un joueur reçoit 403 sur la fédération d'un autre (Policy, vérifié par un test).
- [x] L'écran est utilisable sur téléphone.

## Journal du ticket

- 2026-11-24 : créé.
- 2026-11-24 : lot fait par un sous-agent (config `game.php`, `FederationPolicy`, `StoreFederationRequest`, `FederationController`, pages `federations/Index`, `Create`, `Show`, lien « Mes fédérations », 11 tests), relu. Corrigé par l'agent principal : la limite est vérifiée avant les champs (`StoreFederationRequest::authorize()`), test en plus. Vérifié : `npm run build` OK, `composer ci:check` vert (156 tests passés et 3 ignorés sur 159) ; dans le navigateur (`php artisan serve`, build de production) : liste avec limite atteinte, page d'une fédération, dialogue de suppression, toast « Fédération supprimée », état vide, création, 403 avec le message du palier sur `/federations/create`, affichage téléphone (375 px). Page d'une fédération provisoire : le dashboard vient avec FL-005. Le 403 utilise la page d'erreur par défaut de Laravel.
