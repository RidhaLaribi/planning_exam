<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_id' => 'sometimes|required|exists:modules,id',
            'prof_id' => 'sometimes|required|exists:professeurs,id',
            'salle_id' => 'sometimes|required|exists:lieu_examen,id',
            'date_heure' => 'sometimes|required|date',
            'duree_minutes' => 'sometimes|required|integer|min:30',
        ];
    }
}
