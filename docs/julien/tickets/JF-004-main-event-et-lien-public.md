---
id: JF-004
titre: Main event raconté par l'IA (O1) et lien public de la fédération
statut: en cours          # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/jf-004-schema-ia, puis une branche par lot (une PR par lot)
créé: 2026-11-24
mis-à-jour: 2026-11-24
---
## Contexte

Le cœur du jeu (O1) selon D-004 : le joueur booke un main event dans une de ses fédérations, le lance à sa date, le modèle raconte le match en JSON, le code vérifie la réponse et applique les effets (popularité, règnes de titre, intensité de la rivalité) ; le dashboard (FL-005, Florentin) et la page publique de la fédération les affichent sans rappeler le modèle. Le schéma vient de FL-001 (Florentin, PR #15). Partie de Julien dans `AGENTS.md`.

## Découpage

- **Lot 1, schéma complémentaire et IA en JSON** (`feature/jf-004-schema-ia`) : nouvelle migration (`federations.public_token`, `main_events.ai_call_id`, `rivalries.ended_at`, plus de suppression en cascade des matchs et des règnes quand on supprime un catcheur ou une rivalité) ; `AiGateway::completeJson` avec un schéma JSON (sortie structurée d'OpenRouter), qui rend l'identifiant de la ligne `ai_calls` ; faux fournisseur qui répond un JSON conforme au schéma.
- **Lot 2, moteur sans écran** : prompt compact du main event (sans e-mail ni pseudo), validation de la réponse, `app/Game/PlayMainEvent` (vainqueur, récit, suite logique, popularité bornée, règnes, intensité) dans une transaction.
- **Lot 3, écrans du main event** : liste, détail, création, modification et suppression d'un match prévu (règles de D-004 dans une FormRequest), « Lancer le match » à partir de sa date, les 4 erreurs IA, `MainEventPolicy`.
- **Lot 4, lien public** : activer, couper, régénérer le lien ; `/p/{token}` en lecture seule, données en liste blanche, `noindex`, limite de requêtes.
- **Après les lots** : un vrai appel en production, son coût noté.

## Règles tranchées avec Julien (2026-11-24)

- Popularité : vainqueur +3, perdant −1, +2 de plus pour un match de titre gagné ; bornée de 0 à 100.
- Intensité d'une rivalité : +10 par match de rivalité joué, bornée à 100.
- Lancer un match compte pour 1 appel dans le quota du jour.
- Un catcheur blessé ne peut pas être booké.
- On ne supprime pas un catcheur qui a combattu ou porté un titre, ni une rivalité qui a des matchs (Florentin prévenu par Julien).

## Critères d'acceptation

- [ ] Un main event prévu se crée, se modifie et se supprime selon D-004 ; un match joué est figé.
- [ ] Le match ne se lance qu'à partir de sa date (bouton grisé et refus du serveur).
- [ ] La réponse du modèle est validée (JSON, vainqueur parmi les deux, vainqueur imposé respecté) ; sinon « réponse inexploitable », rien n'est enregistré, la saisie est conservée.
- [ ] Les effets sont appliqués par le code, dans une transaction, et testés sans HTTP ni IA.
- [ ] Chaque lancement passe par le quota et le journal ; le match garde le lien vers sa ligne `ai_calls`.
- [ ] Un joueur reçoit 403 sur la fédération d'un autre (testé).
- [ ] La page publique ne montre ni suite logique, ni e-mail, ni pseudo (testé) ; un lien coupé répond 404.
- [ ] Aucun vrai appel au modèle dans les tests ; 4 checks verts sur chaque PR.
- [ ] Un vrai appel en production réussi, son coût noté.

## Journal du ticket

- 2026-11-24 : créé ; plan et règles validés par Julien ; lot 1 lancé.
