<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfesseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'dept_id' => 'required|exists:departements,id',
            'specialite' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id|unique:professeurs,user_id',
        ];
    }
}
