<?php

namespace App\Mail;

use App\Models\LabSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdmissionBatchMail extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $admissions;

    public ?string $customMessage;

    public ?string $signature;

    public function __construct(Collection $admissions, ?string $customMessage = null)
    {
        $this->admissions = $admissions;
        $this->customMessage = $customMessage;
        $this->signature = LabSetting::get('results_signature', '');
    }

    public function envelope(): Envelope
    {
        $count = $this->admissions->count();
        $subject = LabSetting::get('results_default_subject', 'Informe de Resultados');

        if ($count === 1) {
            $subject = str_replace('{protocol}', $this->admissions->first()->protocol_number, $subject);
        } else {
            $subject = "Informes de Resultados ({$count} protocolos)";
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admission-batch-result');
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->admissions as $admission) {
            $attachments[] = AdmissionResultMail::makePdfAttachment($admission);
        }

        return $attachments;
    }
}
