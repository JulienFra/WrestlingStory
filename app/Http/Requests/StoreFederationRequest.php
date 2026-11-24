<?php

namespace App\Http\Requests;

use App\Models\Federation;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreFederationRequest extends FormRequest
{
    /**
     * The tier limit is checked before the fields: a player at the limit gets the refusal, not form errors.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Federation::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:60',
                Rule::unique('federations')->where('user_id', $this->user()?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Donne un nom à ta fédération.',
            'name.string' => 'Le nom doit être un texte.',
            'name.min' => 'Le nom doit faire au moins :min caractères.',
            'name.max' => 'Le nom ne peut pas dépasser :max caractères.',
            'name.unique' => 'Tu as déjà une fédération qui porte ce nom.',
            'description.string' => 'La description doit être un texte.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
        ];
    }
}
