<?php

namespace App\Http\Requests\TypeChambre;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeChambreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'prix_de_base' => 'sometimes|required|numeric|min:0',
            'capacite_max' => 'sometimes|required|integer|min:1',
            'image' => 'nullable|image|max:4096',
        ];
    }
}
