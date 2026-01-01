<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|max:255',
            'credits' => 'sometimes|required|integer|min:1',
            'formation_id' => 'sometimes|required|exists:formations,id',
            'pre_req_id' => 'nullable|exists:modules,id',
        ];
    }
}
