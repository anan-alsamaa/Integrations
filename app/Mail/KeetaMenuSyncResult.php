<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KeetaMenuSyncResult extends Mailable
{
    use Queueable, SerializesModels;

    public $brandId;
    public $message;

    /**
     * Create a new message instance.
     */
    public function __construct($brandId, $message)
    {
        $this->brandId = $brandId;
        $this->message = $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('AFCOAUP@althawaqh.com', 'Jeffrey Way'),
            subject: "Menu Sync Result for Brand ID: {$this->brandId}"
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.menu_sync_result',
            with: [
                'message' => $this->message,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
