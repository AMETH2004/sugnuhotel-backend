<?php

namespace App\Http\Requests\Chambre;

use App\Models\Chambre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChambreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chambre = $this->route('chambre');

        return [
            'numero_chambre' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('chambres', 'numero_chambre')->ignore($chambre),
            ],
            'type_chambre_id' => 'sometimes|required|exists:type_chambres,id',
            'etage' => 'sometimes|required|integer|min:0',
            'prix_par_nuit' => 'sometimes|required|numeric|min:0',
            'capacite_max' => 'sometimes|required|integer|min:1',
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
