<?php

namespace App\Mail;

use App\Models\LabSetting;
use App\Models\User;
use App\Models\VetAdmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use PDF;

class VetAdmissionResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public VetAdmission $vetAdmission;

    public ?string $customMessage;

    public ?string $signature;

    public function __construct(VetAdmission $vetAdmission, ?string $customMessage = null)
    {
        $this->vetAdmission = $vetAdmission;
        $this->customMessage = $customMessage;
        $this->signature = LabSetting::get('results_signature', '');
    }

    public function envelope(): Envelope
    {
        $subject = sprintf(
            'Resultados Lab Veterinario - %s (%s) - Protocolo %s',
            $this->vetAdmission->animal_name,
            $this->vetAdmission->species->name ?? '',
            $this->vetAdmission->protocol_number
        );

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vet-admission-result');
    }

    public function attachments(): array
    {
        $this->vetAdmission->load([
            'customer', 'veterinarian', 'species',
            'vetTests' => fn ($q) => $q->where('is_validated', true),
            'vetTests.test.parentTests',
            'vetTests.test.childTests',
        ]);

        $validatorId = $this->vetAdmission->vetTests
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? User::find($validatorId) : null;

        $pdf = PDF::loadView('vet.admissions.pdf-mpdf', [
            'vetAdmission' => $this->vetAdmission,
            'validator' => $validator,
        ], [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $filename = self::generatePdfFilename($this->vetAdmission);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                $filename
            )->withMime('application/pdf'),
        ];
    }

    private static function generatePdfFilename(VetAdmission $vetAdmission): string
    {
        $parts = [
            'LabVeterinario',
            $vetAdmission->animal_name,
            $vetAdmission->species->name ?? 'SinEspecie',
            $vetAdmission->date
                ? $vetAdmission->date->format('Y-m-d')
                : now()->format('Y-m-d'),
        ];

        $sanitized = collect($parts)->map(function ($part) {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part);
            $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $clean);
            $clean = preg_replace('/_+/', '_', $clean);

            return trim($clean, '_');
        })->implode('-');

        return $sanitized.'.'.$vetAdmission->protocol_number.'.pdf';
    }
}
