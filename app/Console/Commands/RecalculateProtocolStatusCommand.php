<?php

namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\Sample;
use App\Models\VetAdmission;
use App\Services\ProtocolStatusCalculator;
use Illuminate\Console\Command;

class RecalculateProtocolStatusCommand extends Command
{
    protected $signature = 'protocols:recalculate-status {--only= : admission|vet|sample}';

    protected $description = 'Recalcula status de protocolos según determinaciones/prácticas (v1.102.0)';

    public function handle(ProtocolStatusCalculator $calculator): int
    {
        $only = $this->option('only');

        if (! $only || $only === 'admission') {
            $this->recalculateAdmissions();
        }

        if (! $only || $only === 'vet') {
            $this->recalculateVetAdmissions();
        }

        if (! $only || $only === 'sample') {
            $this->recalculateSamples();
        }

        $this->info('Recálculo de estados completado.');

        return self::SUCCESS;
    }

    private function recalculateAdmissions(): void
    {
        $count = 0;
        Admission::query()
            ->where('status', '!=', Admission::STATUS_CANCELLED)
            ->with(['admissionTests.test.childTests', 'admissionTests.test.children'])
            ->chunkById(100, function ($admissions) use (&$count) {
                foreach ($admissions as $admission) {
                    $admission->update(['status' => $admission->calculated_status]);
                    $count++;
                }
            });

        $this->line("Admisiones clínicas: {$count}");
    }

    private function recalculateVetAdmissions(): void
    {
        $count = 0;
        VetAdmission::query()
            ->where('status', '!=', 'cancelled')
            ->with(['vetTests.test.childTests', 'vetTests.test.children'])
            ->chunkById(100, function ($admissions) use (&$count) {
                foreach ($admissions as $admission) {
                    $admission->update(['status' => $admission->calculated_status]);
                    $count++;
                }
            });

        $this->line("Admisiones veterinarias: {$count}");
    }

    private function recalculateSamples(): void
    {
        $count = 0;
        Sample::query()
            ->where('status', '!=', 'cancelled')
            ->with('determinations')
            ->chunkById(100, function ($samples) use (&$count) {
                foreach ($samples as $sample) {
                    $workStatus = $sample->calculated_status;
                    $validationStatus = match ($workStatus) {
                        ProtocolStatusCalculator::STATUS_VALIDATED => 'validated',
                        ProtocolStatusCalculator::STATUS_PARTIALLY_VALIDATED => 'partial',
                        default => 'pending',
                    };

                    $sample->update([
                        'status' => $workStatus,
                        'validation_status' => $validationStatus,
                    ]);
                    $count++;
                }
            });

        $this->line("Muestras: {$count}");
    }
}
