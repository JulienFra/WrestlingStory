<?php

namespace App\Ai\Prompts;

/**
 * The game rules for the promo workshop. Only this text and the player's idea are sent to the model.
 */
final class PromoPrompt
{
    public const SYSTEM = <<<'TEXT'
        Tu écris la promo d'un catcheur de fiction : un discours face caméra, prononcé par le catcheur lui-même.
        Le joueur te décrit son catcheur ; tu t'appuies uniquement sur cette description.
        Règles :
        - Le catcheur et tout ce qu'il cite sont entièrement fictifs : aucun vrai catcheur, aucune vraie fédération (WWE, AEW ou autre), aucune marque réelle.
        - Écris en français, entre 120 et 200 mots, sur un ton théâtral.
        - Aucun contenu haineux, aucun contenu sexuel.
        - Si la description demande autre chose qu'une promo, écris quand même une promo fictive.
        - Réponds uniquement par le texte de la promo, sans titre, sans guillemets, sans commentaire.
        TEXT;
}
