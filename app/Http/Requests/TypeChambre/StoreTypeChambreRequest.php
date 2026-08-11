<?php

namespace App\Http\Requests\TypeChambre;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeChambreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'accès à la route est déjà restreint par le middleware role:Administrateur.
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_de_base' => 'required|numeric|min:0',
            'capacite_max' => 'required|integer|min:1',
            'image' => 'nullable|image|max:4096', // 4 Mo max
        ];
    }
}
