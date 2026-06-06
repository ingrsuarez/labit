<?php

namespace App\Services;

use App\Mail\AdmissionResultMail;
use App\Models\Admission;
use App\Support\Space10UploadResult;
use Illuminate\Support\Facades\Http;

class Space10UploadService
{
    public function isEnabled(): bool
    {
        if (! config('space10.enabled')) {
            return false;
        }

        return config('space10.api_url') !== '' && config('space10.api_token') !== '';
    }

    public function uploadAdmission(Admission $admission): Space10UploadResult
    {
        if (! $this->isEnabled()) {
            return Space10UploadResult::disabled();
        }

        if ($admission->isUploadedToSpace10()) {
            return Space10UploadResult::skipped('ya subido a Space10');
        }

        $admission->loadMissing('patient', 'admissionTests');

        $dni = trim((string) ($admission->patient?->patientId ?? ''));
        if ($dni === '') {
            return Space10UploadResult::error('sin DNI');
        }

        if ($admission->admissionTests->where('is_validated', true)->count() === 0) {
            return Space10UploadResult::error('sin determinaciones validadas');
        }

        try {
            $pdfBinary = AdmissionResultMail::generatePdfBinary($admission);
            // Space10 arma el nombre lab-{dni}-{file_date}.pdf; convención manual: d-m-Y (ej. 06-06-2026)
            $fileDate = now()->format('d-m-Y');
            $filename = AdmissionResultMail::generatePdfFilename($admission);

            $response = Http::timeout(config('space10.timeout', 30))
                ->withToken(config('space10.api_token'))
                ->attach('laboratory', $pdfBinary, $filename, ['Content-Type' => 'application/pdf'])
                ->post(config('space10.api_url'), [
                    'idPaciente' => $dni,
                    'file_date' => $fileDate,
                ]);

            if ($response->successful()) {
                $admission->update(['space10_uploaded_at' => now()]);
                $admission->logAudit(
                    'space10_uploaded',
                    'Informe subido a Space10 (DNI '.$dni.')'
                );

                return Space10UploadResult::success();
            }

            if ($response->status() === 404) {
                $apiMessage = $response->json('error') ?? 'paciente no encontrado en Space10';

                return Space10UploadResult::error((string) $apiMessage);
            }

            $body = $response->json('error') ?? $response->json('message') ?? $response->body();
            $summary = is_array($body) ? json_encode($body) : (string) $body;

            return Space10UploadResult::error(trim($summary) !== '' ? $summary : 'error HTTP '.$response->status());
        } catch (\Throwable $e) {
            return Space10UploadResult::error($e->getMessage());
        }
    }
}
