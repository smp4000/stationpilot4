<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeePasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Employee $employee,
        public readonly string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihr Zugang zu StationPilot',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-password',
        );
    }
}
