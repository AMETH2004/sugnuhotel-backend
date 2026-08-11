<?php

namespace App\Http\Requests\Reservation;

use App\Models\Chambre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Le personnel (réception/admin) peut réserver pour un client existant.
            'user_id' => 'sometimes|integer|exists:users,id',
            'chambre_id' => 'required|integer|exists:chambres,id',
            'date_arrivee' => 'required|date|after_or_equal:today',
            'date_depart' => 'required|date|after:date_arrivee',
            'nombre_adultes' => 'required|integer|min:1',
            'nombre_enfants' => 'sometimes|integer|min:0',
            'demandes_speciales' => 'nullable|string|max:1000',
            'services' => 'nullable|array',
            'services.*.service_id' => 'required_with:services|integer|exists:services,id',
            'services.*.quantite' => 'sometimes|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'date_arrivee.after_or_equal' => "La date d'arrivée ne peut pas être dans le passé.",
            'date_depart.after' => "La date de départ doit être postérieure à la date d'arrivée.",
        ];
    }

    /**
     * Validation métier supplémentaire : capacité de la chambre et disponibilité.
     * La vérification définitive du double-booking (avec verrou de ligne) est
     * refaite dans une transaction côté contrôleur pour éviter toute condition de course.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $chambre = Chambre::find($this->input('chambre_id'));

            if (!$chambre) {
                return; // déjà signalé par la règle "exists"
            }

            $occupants = (int) $this->input('nombre_adultes', 0) + (int) $this->input('nombre_enfants', 0);

            if ($occupants > $chambre->capacite_max) {
                $validator->errors()->add(
                    'nombre_adultes',
                    "Cette chambre accueille au maximum {$chambre->capacite_max} personne(s)."
                );
            }

            if ($this->filled('date_arrivee') && $this->filled('date_depart')
                && !$chambre->estDisponiblePour($this->input('date_arrivee'), $this->input('date_depart'))) {
                $validator->errors()->add(
                    'chambre_id',
                    'Cette chambre est déjà réservée sur la période sélectionnée.'
                );
            }
        });
    }
}
