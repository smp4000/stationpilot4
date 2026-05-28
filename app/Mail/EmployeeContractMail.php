<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeContractMail extends Mailable
{
    use Queueable, SerializesModels;

    public Employee $employee;

    public function __construct(
        public EmployeeContract $contract,
        public string $token,
    ) {
        $this->employee = $contract->employee;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihr Arbeitsvertrag — bitte digital unterschreiben',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-contract',
            with: [
                'contract' => $this->contract,
                'employee' => $this->employee,
                'signUrl'  => route('contract.sign', $this->token),
            ],
        );
    }
}
