@component('mail::message')
# Réservation annulée

Bonjour {{ $reservation->user->name }},

Votre réservation **{{ $reservation->numero_reservation }}** (chambre N°{{ $reservation->chambre->numero_chambre }},
du {{ \Carbon\Carbon::parse($reservation->date_arrivee)->format('d/m/Y') }}
au {{ \Carbon\Carbon::parse($reservation->date_depart)->format('d/m/Y') }}) a bien été annulée.

Si vous n'êtes pas à l'origine de cette annulation, contactez immédiatement la réception.

L'équipe SugnuHotel
@endcomponent
