<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfesseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|max:255',
            'dept_id' => 'sometimes|required|exists:departements,id',
            'specialite' => 'sometimes|required|string|max:255',
            'user_id' => 'sometimes|required|exists:users,id|unique:professeurs,user_id,' . $this->professeur->id,
        ];
    }
}
