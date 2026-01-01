<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLieuExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|max:255',
            'capacite' => 'sometimes|required|integer|min:1',
            'type' => 'sometimes|required|string|max:255',
            'batiment' => 'sometimes|required|string|max:255',
        ];
    }
}
