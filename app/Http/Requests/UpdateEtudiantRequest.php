<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEtudiantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'formation_id' => 'sometimes|required|exists:formations,id',
            'promo' => 'sometimes|required|string|max:20',
            'user_id' => 'sometimes|required|exists:users,id|unique:etudiants,user_id,' . $this->etudiant->id,
        ];
    }
}
