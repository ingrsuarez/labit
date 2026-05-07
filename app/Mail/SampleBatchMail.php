<?php

namespace App\Mail;

use App\Models\LabSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use PDF;

class SampleBatchMail extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $samples;

    public ?string $customMessage;

    public ?string $signature;

    public function __construct(Collection $samples, ?string $customMessage = null)
    {
        $this->samples = $samples;
        $this->customMessage = $customMessage;
        $this->signature = LabSetting::get('results_signature', '');
    }

    public function envelope(): Envelope
    {
        $count = $this->samples->count();
        $subject = LabSetting::get('results_default_subject', 'Informe de Resultados');

        if ($count === 1) {
            $subject = str_replace('{protocol}', $this->samples->first()->protocol_number, $subject);
        } else {
            $subject = "Informes de Resultados ({$count} protocolos)";
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sample-batch-result');
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->samples as $sample) {
            $sample->load([
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

            $pdf = PDF::loadView('sample.pdf-mpdf', ['sample' => $sample], [], [
                'margin_top' => 35,
                'margin_bottom' => 20,
                'margin_left' => 15,
                'margin_right' => 15,
            ]);

            $filename = SampleResultMail::generatePdfFilename($sample);

            $attachments[] = Attachment::fromData(
                fn () => $pdf->output(),
                $filename
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
