<?php

namespace App\Http\Requests\Chambre;

use App\Models\Chambre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChambreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_chambre' => 'required|string|max:50|unique:chambres,numero_chambre',
            'type_chambre_id' => 'required|exists:type_chambres,id',
            'etage' => 'required|integer|min:0',
            'prix_par_nuit' => 'required|numeric|min:0',
            'capacite_max' => 'required|integer|min:1',
            'statut' => ['sometimes', Rule::in([
                Chambre::STATUT_DISPONIBLE,
                Chambre::STATUT_OCCUPEE,
                Chambre::STATUT_MAINTENANCE,
                Chambre::STATUT_HORS_SERVICE,
            ])],
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:4096',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
        ];
    }
}
