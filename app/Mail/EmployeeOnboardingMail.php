<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\GeneratedDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeOnboardingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Employee            $employee
     * @param GeneratedDocument[] $generatedDocuments
     * @param EmployeeContract[]  $contracts
     */
    public function __construct(
        public Employee $employee,
        public array $generatedDocuments = [],
        public array $contracts = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Onboarding-Dokumente — bitte digital unterschreiben',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-onboarding',
            with: [
                'employee'           => $this->employee,
                'generatedDocuments' => $this->generatedDocuments,
                'contracts'          => $this->contracts,
            ],
        );
    }
}
