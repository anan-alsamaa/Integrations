<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class OrderProcessingError extends Mailable
{
    use Queueable, SerializesModels;

    protected $orderDetails;
    protected $errorMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($orderDetails, $errorMessage)
    {
        $this->orderDetails = $orderDetails;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('AFCOAUP@althawaqh.com', 'Jeffrey Way'),
            subject: 'Keeta Order Processing Error'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_error',
            with: [
                'orderDetails' => $this->orderDetails,
                'errorMessage' => $this->errorMessage,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
