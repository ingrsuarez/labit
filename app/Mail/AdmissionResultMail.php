<?php

namespace App\Mail;

use App\Models\Admission;
use App\Models\LabSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use PDF;

class AdmissionResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public Admission $admission;

    public ?string $customMessage;

    public ?string $signature;

    public function __construct(Admission $admission, ?string $customMessage = null)
    {
        $this->admission = $admission;
        $this->customMessage = $customMessage;
        $this->signature = LabSetting::get('results_signature', '');
    }

    public function envelope(): Envelope
    {
        $subject = LabSetting::get('results_default_subject', 'Informe de Resultados');
        $subject = str_replace('{protocol}', $this->admission->protocol_number, $subject);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admission-result');
    }

    public function attachments(): array
    {
        $this->admission->load([
            'patient',
            'insuranceRelation',
            'admissionTests' => fn ($q) => $q->where('is_validated', true),
            'admissionTests.test.parentTests',
            'admissionTests.test.childTests',
            'admissionTests.test.referenceValues.category',
            'creator',
        ]);

        $validatorId = $this->admission->admissionTests
            ->pluck('validated_by')
            ->countBy()->sortDesc()->keys()->first();
        $validator = $validatorId ? User::find($validatorId) : null;

        $pdf = PDF::loadView('lab.admissions.pdf-mpdf', [
            'admission' => $this->admission,
            'validator' => $validator,
        ], [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $filename = self::generatePdfFilename($this->admission);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                $filename
            )->withMime('application/pdf'),
        ];
    }

    private static function generatePdfFilename(Admission $admission): string
    {
        $parts = [
            'LabClinico',
            $admission->patient?->name ?? 'SinPaciente',
            $admission->date
                ? $admission->date->format('Y-m-d')
                : now()->format('Y-m-d'),
        ];

        $sanitized = collect($parts)->map(function ($part) {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part);
            $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $clean);
            $clean = preg_replace('/_+/', '_', $clean);

            return trim($clean, '_');
        })->implode('-');

        return $sanitized.'.'.$admission->protocol_number.'.pdf';
    }
}
