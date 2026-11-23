---
id: D-003
titre: Mistral Small 3.2 (24B) via OpenRouter, sur des fournisseurs sans rétention ni réutilisation des données
statut: proposée          # proposée | acceptée | remplacée par D-00N
proposée-par: julien
date: 2026-11-23
---
## Contexte

Tous les appels au modèle passent par le serveur, vers OpenRouter. Le budget est d'1 € pour tout le projet. L'énoncé demande de limiter le transfert des données (`"provider": { "zdr": true, "data_collection": "deny" }` ou un fournisseur européen) et de justifier le choix du modèle (qualité, coût, latence, alternatives). Le jeu écrit en français : des promos de catcheurs pour la revue n°1, puis la narration d'un segment avec des effets en JSON (O1). Prix relevés le 2026-11-23 sur l'API publique d'OpenRouter (`/api/v1/endpoints/zdr`), en dollars par million de jetons (entrée / sortie).

## Décision

- **Modèle** : `mistralai/mistral-small-3.2-24b-instruct` (config `AI_MODEL`, modifiable sans code).
- **Chaque requête** envoie `provider: { zdr: true, data_collection: "deny" }` : OpenRouter ne la route que vers un fournisseur qui ne garde pas les données et ne s'en sert pas pour entraîner. Elle demande aussi `usage: { include: true }`, pour lire les jetons et le coût réels.
- **Prix au moment du choix** : 0,06 / 0,16 $ chez DeepInfra (0,075 / 0,20 $ chez un second fournisseur).
- **Ordre de grandeur** : une promo, soit environ 400 jetons en entrée et 300 en sortie, coûte environ 0,00007 $. 1 € couvre donc plus de 10 000 appels.
- **Les tests** utilisent un faux fournisseur (aucun appel réel). Un réglage admin bascule en production entre le vrai modèle et le faux, pour la démo des erreurs (O5).

## Écarté

- **Mistral Nemo** (environ 0,02 / 0,02 $) : encore moins cher, mais plus ancien et moins fiable pour suivre un format JSON strict, dont on aura besoin en O1. Il reste le repli si le budget serre.
- **Llama 3.1 8B** (0,02 / 0,04 $) : bon marché, mais nettement plus faible en français.
- **gpt-oss-20b** (environ 0,02 / 0,09 $) : modèle de raisonnement, réponses plus longues et plus lentes pour des textes courts.
- **Modèles `:free`** : gratuits, mais leur limite de débit est partagée entre tous leurs utilisateurs. La démo devant le professeur pourrait échouer sur une limite qu'on ne contrôle pas.
- **Ministral 3B et 8B chez Mistral** (fournisseur européen, 0,10 à 0,15 $) : plus chers pour une qualité plus faible que Small 3.2. On garde l'option `only: ["mistral/eu"]` si l'hébergement en Europe devient une exigence.

## Conséquences

- La clé OpenRouter (`OPENROUTER_API_KEY`) vit seulement dans le `.env` du VPS et dans le `.env` local de Julien, jamais dans le dépôt ni le navigateur. Elle est plafonnée à 0,50 € côté OpenRouter (choix de Julien, le 2026-11-23) : la moitié du budget reste en réserve, et une boucle ou un bug ne peut pas dépasser ce plafond. Une fois atteint, OpenRouter répond 402 et le jeu affiche « crédit épuisé » (O5).
- Les prix changent : le coût affiché dans le tableau de bord admin vient de la réponse d'OpenRouter (`usage.cost`), pas d'un prix codé en dur.
- Si aucun fournisseur `zdr` n'est disponible pour ce modèle, l'appel échoue (« fournisseur en panne ») au lieu de partir chez un fournisseur qui garde les données.
- Le choix sera réévalué avec les coûts observés avant le calcul du pricing (O6), et pour la narration d'O1.
