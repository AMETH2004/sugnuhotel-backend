@component('mail::message')
# Réservation modifiée

Bonjour {{ $reservation->user->name }},

Votre réservation **{{ $reservation->numero_reservation }}** a été mise à jour. Voici le récapitulatif actuel :

@component('mail::table')
| Détail | Information |
| :----- | :----------- |
| Chambre | N°{{ $reservation->chambre->numero_chambre }} ({{ $reservation->chambre->typeChambre->nom }}) |
| Arrivée | {{ \Carbon\Carbon::parse($reservation->date_arrivee)->format('d/m/Y') }} |
| Départ | {{ \Carbon\Carbon::parse($reservation->date_depart)->format('d/m/Y') }} |
| Statut | {{ $reservation->statut }} |
| Prix total | {{ number_format($reservation->prix_total, 0, ',', ' ') }} FCFA |
@endcomponent

Si vous n'êtes pas à l'origine de cette modification, contactez immédiatement la réception.

L'équipe SugnuHotel
@endcomponent
