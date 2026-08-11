<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationModifieeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre réservation ' . $this->reservation->numero_reservation . ' a été modifiée - SugnuHotel',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reservations.modifiee',
            with: ['reservation' => $this->reservation],
        );
    }
}
