---
id: D-004
titre: Règles du jeu — fédérations, catcheurs, titres, rivalités, main events et partage public
statut: acceptée          # proposée | acceptée | remplacée par D-00N
proposée-par: julien
date: 2026-11-24
---
## Contexte

Après la revue n°1, il manque le cœur du jeu : des données métier persistantes (O2) et une fonction IA dont le résultat est enregistré et réutilisé sans rappeler le modèle (O1). Julien et Florentin ont listé ce que le joueur doit pouvoir faire : créer une fédération, y créer des catcheurs, des titres et des rivalités, booker un main event que l'IA raconte, voir le dashboard de la fédération, et partager sa fédération par un lien. Ces règles ont été tranchées le 2026-11-24 par Julien, et acceptées par Florentin à l'oral le même jour.

## Décision

- **Fédérations** :
  - un joueur peut avoir plusieurs fédérations : **1 en Gratuit, 10 en Pro**. Le serveur vérifie la limite à la création. Un joueur repassé en Gratuit garde ses fédérations, mais n'en crée plus tant qu'il en a au moins une ;
  - la fédération est dans l'adresse (`/federations/{id}/…`) : pas de « fédération en cours » à retenir ;
  - supprimer une fédération supprime tout ce qu'elle contient, après confirmation.
- **Catcheurs** : ils appartiennent à une fédération et n'en changent jamais (pas de transfert).
- **Titres** :
  - un titre appartient à une fédération ; il a un champion ou il est vacant ;
  - on garde l'historique des règnes (qui, depuis quand, jusqu'à quand).
- **Rivalités** :
  - une rivalité relie 2 catcheurs de la même fédération, avec un titre optionnel ;
  - elle est **en cours** ou **terminée**. Un catcheur n'a qu'**une seule rivalité en cours** à la fois ;
  - elle se termine **seulement** quand le joueur clique sur « Terminer la rivalité ».
- **Main event**, de deux types :
  - **match simple** : le joueur choisit 2 catcheurs différents de la fédération, et un titre optionnel ;
  - **match de rivalité** : le joueur choisit une rivalité en cours, qui donne les 2 catcheurs et le titre éventuel. Le serveur reprend ceux de la rivalité, quoi qu'envoie le navigateur.
- **Match de titre** : si le titre a un champion, celui-ci doit faire partie du match. Si le titre est vacant, le vainqueur devient le premier champion. Si le challenger gagne, le règne en cours se termine et un nouveau commence.
- **Date de sortie** :
  - le joueur choisit un jour réel, jamais dans le passé. Le main event apparaît dans les « matchs à venir » ;
  - le bouton « Lancer le match » est grisé avant cette date, et **le serveur refuse** de le lancer avant (heure de Bruxelles) ;
  - un main event prévu se modifie ou se supprime ; une fois joué, il est figé.
- **Vainqueur** :
  - le joueur peut l'imposer, et l'IA raconte alors un match dont l'issue est déjà décidée ;
  - s'il ne dit rien, l'IA le choisit. Son choix n'est accepté que s'il est l'un des 2 catcheurs du match ; sinon, l'appel compte comme une « réponse inexploitable » (O5), rien n'est enregistré et la saisie est conservée.
- **Ce que l'IA renvoie** (en JSON, validé par le code) :
  - le récit du match ;
  - le vainqueur, s'il n'est pas imposé ;
  - une **suite logique** : un simple texte d'idées, enregistré avec le match, visible **seulement par le propriétaire**. Elle ne déclenche aucune action.
- **Effets** : c'est le code qui les calcule, jamais l'IA. Popularité du vainqueur en hausse, celle du perdant en légère baisse, avec des deltas bornés ; règnes de titre mis à jour.
- **Partage public de la fédération** :
  - le propriétaire active ou coupe un lien avec un code aléatoire impossible à deviner. Le lien s'ouvre sans compte ;
  - la page est en **lecture seule** et montre les catcheurs, les titres et leurs champions (avec l'historique des règnes), les rivalités, les main events à venir et l'historique des matchs joués avec leur récit ;
  - elle ne montre ni la suite logique, ni l'e-mail ou le pseudo du propriétaire, ni aucun bouton d'action. Elle passe par des routes à part, qui ne font que lire.

## Écarté

- **Une seule fédération par joueur** : plus simple, mais sans plusieurs univers, et sans argument de plus pour le palier Pro.
- **Un catcheur dans plusieurs rivalités à la fois** : écarté, pour que chaque rivalité reste lisible.
- **Une rivalité terminée automatiquement** (après N matchs, ou sur proposition de l'IA) : le joueur garde la main.
- **Un match joué automatiquement à sa date** : il faudrait une tâche planifiée sur le VPS, un appel payant lancé sans le joueur, et une erreur IA que personne ne verrait.
- **Une suite logique sous forme de prochain match à accepter** : un texte d'idées suffit, sans effet à valider.
- **Un lien de partage par main event** : le lien de la fédération montre déjà tous les matchs.

## Conséquences

- Le schéma (migrations, modèles, seeders, une PR à deux) contient au moins : `federations`, `wrestlers`, `titles`, `title_reigns`, `rivalries`, `main_events`. Tous portent un `federation_id`, sauf les règnes, qui passent par le titre. Les données de démo sont entièrement fictives.
- La règle « une seule rivalité en cours par catcheur » est vérifiée par le serveur, et appuyée par un index en base contre le double clic.
- Chaque action passe par une Policy (« cette fédération est-elle à moi ? ») et par une FormRequest. Toutes ces règles tiennent même si le formulaire est modifié dans le navigateur.
- L'atelier promo reste une démo : O1, c'est le main event (le joueur fournit le match, le modèle produit le récit et le vainqueur, le code applique les effets que le dashboard et la page publique affichent sans rappeler le modèle).
- La limite de fédérations est un argument du palier Pro pour la landing et le pricing (O6).
