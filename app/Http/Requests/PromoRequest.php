<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PromoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idea' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'idea.required' => 'Décris ton catcheur avant de générer sa promo.',
            'idea.string' => 'La description doit être un texte.',
            'idea.min' => 'La description doit faire au moins :min caractères.',
            'idea.max' => 'La description ne peut pas dépasser :max caractères.',
        ];
    }
}
