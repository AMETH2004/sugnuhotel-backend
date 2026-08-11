<?php

namespace App\Http\Requests\Reservation;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chambre_id' => 'sometimes|integer|exists:chambres,id',
            'date_arrivee' => 'sometimes|required|date',
            'date_depart' => 'sometimes|required|date|after:date_arrivee',
            'nombre_adultes' => 'sometimes|required|integer|min:1',
            'nombre_enfants' => 'sometimes|integer|min:0',
            'demandes_speciales' => 'nullable|string|max:1000',
            'statut' => ['sometimes', Rule::in([
                Reservation::STATUT_EN_ATTENTE,
                Reservation::STATUT_CONFIRMEE,
                Reservation::STATUT_ENREGISTREE,
                Reservation::STATUT_TERMINEE,
                Reservation::STATUT_ANNULEE,
            ])],
        ];
    }

    /**
     * Revérifie la disponibilité de la chambre si les dates ou la chambre changent.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Reservation $reservation */
            $reservation = $this->route('reservation');

            if (!$reservation || (!$this->filled('date_arrivee') && !$this->filled('date_depart') && !$this->filled('chambre_id'))) {
                return;
            }

            $chambre = $this->filled('chambre_id')
                ? \App\Models\Chambre::find($this->input('chambre_id'))
                : $reservation->chambre;

            $dateArrivee = $this->input('date_arrivee', $reservation->date_arrivee->toDateString());
            $dateDepart = $this->input('date_depart', $reservation->date_depart->toDateString());

            if ($chambre && !$chambre->estDisponiblePour($dateArrivee, $dateDepart, $reservation->id)) {
                $validator->errors()->add(
                    'chambre_id',
                    'Cette chambre est déjà réservée sur la période sélectionnée.'
                );
            }
        });
    }
}
