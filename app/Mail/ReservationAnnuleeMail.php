<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationAnnuleeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Annulation de votre réservation ' . $this->reservation->numero_reservation . ' - SugnuHotel',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reservations.annulee',
            with: ['reservation' => $this->reservation],
        );
    }
}
