@component('mail::message')
# Réservation confirmée !

Bonjour {{ $reservation->user->name }},

Votre réservation à **SugnuHotel** est confirmée. Voici le récapitulatif :

@component('mail::table')
| Détail | Information |
| :----- | :----------- |
| Numéro de réservation | **{{ $reservation->numero_reservation }}** |
| Chambre | N°{{ $reservation->chambre->numero_chambre }} ({{ $reservation->chambre->typeChambre->nom }}) |
| Arrivée | {{ \Carbon\Carbon::parse($reservation->date_arrivee)->format('d/m/Y') }} |
| Départ | {{ \Carbon\Carbon::parse($reservation->date_depart)->format('d/m/Y') }} |
| Voyageurs | {{ $reservation->nombre_adultes }} adulte(s), {{ $reservation->nombre_enfants }} enfant(s) |
| Prix total | {{ number_format($reservation->prix_total, 0, ',', ' ') }} FCFA |
@endcomponent

@if($reservation->services->isNotEmpty())
**Services additionnels :**
@foreach($reservation->services as $service)
- {{ $service->nom }} (x{{ $service->pivot->quantite }})
@endforeach
@endif

Merci de votre confiance et à bientôt !

L'équipe SugnuHotel
@endcomponent
