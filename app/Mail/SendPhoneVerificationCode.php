<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendPhoneVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationCode; // Ubah jadi public

    /**
     * Create a new message instance.
     */
    public function __construct(string $code) // Terima kode di constructor
    {
        $this->verificationCode = $code;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Phone Verification Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Buat view email sederhana di resources/views/emails/phone-verification.blade.php
        return new Content(
            view: 'emails.phone-verification',
            with: [
                'code' => $this->verificationCode, // Kirim kode ke view
            ],
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
