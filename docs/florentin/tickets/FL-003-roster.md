---
id: FL-003
titre: Roster : créer, modifier et lister les catcheurs d'une fédération
statut: ouvert            # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/fl-003-roster
créé: 2026-11-24
mis-à-jour: 2026-11-24
---
## Contexte

Le créateur de catcheur (outil secondaire d'`AGENTS.md`), d'abord en formulaire manuel ; une version assistée par l'IA pourra suivre dans un autre ticket. Les stats servent aux matchs de Julien. Dépend de FL-001 et FL-002.

## Critères d'acceptation

- [ ] Le joueur crée un catcheur (nom de ring, gimmick, alignement, 5 stats de 0 à 100) dans une de ses fédérations.
- [ ] Une stat hors bornes ou un nom déjà pris dans la fédération est refusé par une FormRequest, message en français, saisie conservée.
- [ ] Le joueur modifie un catcheur et voit la liste du roster ; il ne peut rien faire dans la fédération d'un autre (403, testé).
- [ ] L'écran est utilisable sur téléphone.

## Journal du ticket

- 2026-11-24 : créé.
