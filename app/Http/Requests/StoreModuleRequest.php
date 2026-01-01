<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'credits' => 'required|integer|min:1',
            'formation_id' => 'required|exists:formations,id',
            'pre_req_id' => 'nullable|exists:modules,id',
        ];
    }
}
