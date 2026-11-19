---
id: JF-002
titre: Pseudo unique sans caractère spécial et e-mail au bon format, vérifiés par le serveur
statut: terminé           # ouvert | en cours | bloqué | terminé | abandonné
branche: feature/squelette-laravel
créé: 2026-11-19
mis-à-jour: 2026-11-19
---
## Contexte

Le starter kit accepte n'importe quel nom (espaces, doublons) et des e-mails comme `julien@site`. Julien veut un pseudo de joueur propre et unique, et des règles qui tiennent même si on modifie le formulaire dans les outils du navigateur (F12). Fait sur la branche du squelette, dans la même PR que JF-001, à la demande de Julien. Les comptes (O3) sont la partie de Florentin : à lui signaler dans la PR.

## Critères d'acceptation

- [x] Le pseudo (colonne `name`) fait 3 à 30 caractères, seulement `A-Z`, `a-z`, `0-9` et `_`, à l'inscription et à la modification du profil.
- [x] Le pseudo est unique sans tenir compte de la casse (`Julien` bloque `julien`), en validation et par un index unique `lower(name)` en base.
- [x] L'e-mail doit avoir un format réel (`email:rfc,filter` : `julien@site` refusé, `julien@site.be` accepté).
- [x] Une requête modifiée (champ retiré, tableau à la place d'un texte) donne une erreur de validation, jamais une erreur 500, à l'inscription, à la connexion et au mot de passe oublié.
- [x] Les messages du pseudo et de l'e-mail sont en français, et le joueur garde sa saisie.

## Journal du ticket

- 2026-11-19 : créé et fait. Règles dans `ProfileValidationRules`, nouvelle migration d'index, middleware `EnsureEmailIsText` sur les routes Fortify, clé du limiteur de connexion protégée. Les tests ont révélé deux erreurs 500 du kit avec `email[]=…` (inscription, connexion), corrigées. Vérifiés : `composer ci:check` (54 tests, 51 réussis, 3 ignorés), `npm run build`, essai dans le navigateur avec `required`/`pattern` retirés à la main (messages français affichés, saisie conservée). Non vérifié : téléphone.
- 2026-11-19 (nuit) : essai sous `composer dev` : profil avec `Demo Booker!` (attributs HTML retirés) refusé avec le message français, base inchangée ; inscription `demo_booker` refusée (« Ce pseudo est déjà pris. ») car `Demo_Booker` existe.
- 2026-11-19 (nuit) : terminé. Florentin d'accord sans relecture formelle ; fusionné avec la PR #2.
