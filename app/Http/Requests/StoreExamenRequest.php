<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_id' => 'required|exists:modules,id',
            'prof_id' => 'required|exists:professeurs,id',
            'salle_id' => 'required|exists:lieu_examen,id',
            'date_heure' => 'required|date|after:now',
            'duree_minutes' => 'required|integer|min:30',
        ];
    }
}
