<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLieuExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'capacite' => 'required|integer|min:1',
            'type' => 'required|string|max:255',
            'batiment' => 'required|string|max:255',
        ];
    }
}
