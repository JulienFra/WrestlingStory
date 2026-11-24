# Appeler le modèle de langage

Tout appel passe par `App\Ai\AiGateway`, jamais directement par un client. Le gateway vérifie le quota du jour **avant** l'appel, chronomètre, refuse une réponse vide ou trop longue, et écrit une ligne dans `ai_calls` à chaque tentative (réussie, échouée ou refusée par le quota). Choix du modèle : D-003.

```php
use App\Ai\AiException;
use App\Ai\AiGateway;
use App\Ai\QuotaExceededException;

try {
    $completion = app(AiGateway::class)->complete(
        user: $request->user(),
        feature: 'promo',            // nom court de la fonction, visible dans le tableau de bord
        system: $systemPrompt,       // consignes du jeu, écrites dans le code
        prompt: $validated['idea'],  // seulement le texte du joueur : jamais son e-mail, son pseudo, son id
        maxTokens: 400,
        maxChars: 2000,
    );
} catch (QuotaExceededException|AiException $e) {
    // Message français prêt à afficher ; la saisie du joueur est conservée.
    return back()->withInput()->withErrors(['ai' => $e->getMessage()]);
}
```

## Une réponse en JSON

Pour une réponse structurée (le main event), `completeJson` impose un schéma JSON au modèle (`response_format: json_schema`, `strict`, et `provider.require_parameters` pour n'aller que chez un fournisseur qui le respecte). Il rend un `AiJsonCompletion` : l'objet décodé (`data`) et l'id de la ligne `ai_calls` (`aiCallId`).

```php
$result = app(AiGateway::class)->completeJson(
    user: $request->user(),
    feature: 'main_event',
    system: $systemPrompt,
    prompt: $compactState,        // l'état compact du match : jamais d'e-mail, de pseudo ni d'id d'utilisateur
    maxTokens: 700,
    schemaName: 'main_event',
    schema: $schema,              // strict : additionalProperties false, toutes les propriétés dans required
    isUsable: fn (array $data) => /* types, bornes, vainqueur parmi les deux… */ true,
);
```

Une réponse qui n'est pas un objet JSON, ou que `isUsable` refuse, devient « réponse inexploitable » (O5) ; ses jetons sont quand même journalisés. Le faux fournisseur construit une réponse à partir du schéma : la première valeur d'un `enum`, sinon `minimum`, sinon un texte fictif.

## Les pièces

| Fichier | Rôle |
| --- | --- |
| `app/Ai/AiClient.php` | l'interface : `complete(system, prompt, maxTokens, ?AiJsonSchema): AiCompletion` |
| `app/Ai/OpenRouterClient.php` | le vrai fournisseur : clé `OPENROUTER_API_KEY`, `provider: { zdr: true, data_collection: deny }`, coût lu dans `usage.cost` |
| `app/Ai/FakeAiClient.php` | le faux fournisseur : réponse fixe (ou construite depuis le schéma JSON), ou une des 4 erreurs |
| `app/Ai/AiProvider.php` | le fournisseur courant : réglage admin `Setting::get('ai.provider')`, sinon `AI_PROVIDER` |
| `app/Ai/AiErrorKind.php` | les 4 erreurs d'O5 et leur message en français |
| `config/ai.php` | modèle, délai, quotas par palier (`free` : 10 appels par jour, `pro` : 100) |

## Règles

- **La sortie reste une entrée non fiable.** Le gateway ne vérifie que la longueur : la fonction qui l'appelle valide le contenu (schéma JSON, entités qui existent, deltas bornés) avant de s'en servir.
- **Dans les tests**, aucun appel réel n'est possible : `Http::preventStrayRequests()` est actif dans `tests/TestCase.php`. On utilise `Http::fake` pour `OpenRouterClient`, et le faux fournisseur (`config(['ai.default_provider' => 'fake'])`) pour le reste.
- **Pour provoquer une erreur** en démo : le réglage admin `ai.provider` (`fake_timeout`, `fake_outage`, `fake_credit`, `fake_invalid`).
