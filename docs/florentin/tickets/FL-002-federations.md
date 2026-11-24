---
id: FL-002
titre: Créer et lister ses fédérations, limite par palier
statut: ouvert            # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/fl-002-federations
créé: 2026-11-24
mis-à-jour: 2026-11-24
---
## Contexte

Premier écran du jeu : le joueur crée sa fédération. Le palier limite leur nombre (free : 1, pro : 10), ce qui alimente le pricing (O6). Dépend de FL-001.

## Critères d'acceptation

- [ ] Un joueur crée une fédération depuis un formulaire et la retrouve dans sa liste.
- [ ] Un joueur free qui a déjà 1 fédération ne peut pas en créer une deuxième (refus côté serveur, message en français) ; un joueur pro est bloqué à 10.
- [ ] Les limites sont dans la config (`config/game.php` ou équivalent), pas codées en dur dans le contrôleur.
- [ ] Un joueur reçoit 403 sur la fédération d'un autre (Policy, vérifié par un test).
- [ ] L'écran est utilisable sur téléphone.

## Journal du ticket

- 2026-11-24 : créé.
