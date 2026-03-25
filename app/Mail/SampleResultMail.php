<?php

namespace App\Mail;

use App\Models\LabSetting;
use App\Models\Sample;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use PDF;

class SampleResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sample $sample;

    public ?string $customMessage;

    public ?string $signature;

    public function __construct(Sample $sample, ?string $customMessage = null)
    {
        $this->sample = $sample;
        $this->customMessage = $customMessage;
        $this->signature = LabSetting::get('results_signature', '');
    }

    public function envelope(): Envelope
    {
        $subject = LabSetting::get('results_default_subject', 'Informe de Resultados');
        $subject = str_replace('{protocol}', $this->sample->protocol_number, $subject);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sample-result');
    }

    public function attachments(): array
    {
        $this->sample->load([
            'customer',
            'determinations.test.parentTest',
            'determinations.test.parentTests',
            'determinations.test.children',
            'determinations.test.childTests',
            'determinations.test.defaultReferenceCategory',
            'determinations.referenceCategory',
            'determinations.determinationValidator',
            'creator',
            'validator',
        ]);

        $pdf = PDF::loadView('sample.pdf-mpdf', ['sample' => $this->sample], [], [
            'margin_top' => 35,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $filename = self::generatePdfFilename($this->sample);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                $filename
            )->withMime('application/pdf'),
        ];
    }

    private static function generatePdfFilename(Sample $sample): string
    {
        $parts = [
            $sample->sample_type ?? 'Protocolo',
            $sample->customer?->name ?? 'SinCliente',
            $sample->sampling_date
                ? \Carbon\Carbon::parse($sample->sampling_date)->format('Y-m-d')
                : now()->format('Y-m-d'),
        ];

        $sanitized = collect($parts)->map(function ($part) {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part);
            $clean = preg_replace('/[^A-Za-z0-9_-]/', '_', $clean);
            $clean = preg_replace('/_+/', '_', $clean);

            return trim($clean, '_');
        })->implode('-');

        return $sanitized.'.'.$sample->protocol_number.'.pdf';
    }
}
