---
id: FL-004
titre: Titres (avec champion) et rivalités
statut: ouvert            # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/fl-004-titres-rivalites
créé: 2026-11-24
mis-à-jour: 2026-11-24
---
## Contexte

Les titres et les rivalités donnent de l'enjeu aux matchs de Julien (le main event peut créer une rivalité). Dépend de FL-001 à FL-003.

## Critères d'acceptation

- [ ] Le joueur crée un titre et désigne son champion parmi les catcheurs de la fédération ; changer de champion ferme le règne précédent (testé).
- [ ] Le joueur crée une rivalité entre deux catcheurs différents de sa fédération ; un catcheur d'une autre fédération est refusé (testé).
- [ ] Le joueur peut clore une rivalité.
- [ ] Le code vérifie que les catcheurs d'une rivalité et le champion d'un titre sont dans la même fédération (la base ne le vérifie pas, voir FL-001).
- [ ] Deux rivalités actives sur la même paire (A contre B ou B contre A) sont refusées.
- [ ] Changer de champion ferme l'ancien règne puis ouvre le nouveau, dans une transaction (la base refuse deux règnes ouverts).
- [ ] Les règles (changer de champion) vivent dans `app/Game/`, testables sans HTTP.
- [ ] Les écrans sont utilisables sur téléphone.

## Journal du ticket

- 2026-11-24 : créé.
