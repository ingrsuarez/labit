<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\User;
use App\Services\AdmissionSampleDrawService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Backfill de extracciones históricas (admisiones anteriores al despliegue de v1.110.0).
 *
 * Asigna sample_drawn_by / sample_drawn_at en admisiones clínicas que requieren
 * extracción y aún no tienen tomador, usando por defecto a la directora técnica
 * (Clara Silvina Olie). No genera registros de auditoría.
 *
 * Uso: php artisan db:seed --class=LabSampleDrawHistoricalBackfillSeeder
 *
 * Opcional en .env:
 *   SAMPLE_DRAW_BACKFILL_USER_ID=3
 *   SAMPLE_DRAW_BACKFILL_BEFORE=2026-05-29 23:59:59
 */
class LabSampleDrawHistoricalBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $drawer = $this->resolveDefaultDrawer();
        if (! $drawer) {
            $this->command->error(
                'No se encontró usuario para el backfill. '
                .'Configure SAMPLE_DRAW_BACKFILL_USER_ID o verifique que exista la directora técnica (Olie / Clara).'
            );

            return;
        }

        $service = app(AdmissionSampleDrawService::class);
        $cutoff = $this->resolveCutoff();
        $updated = 0;
        $skipped = 0;

        Admission::query()
            ->whereNull('sample_drawn_by')
            ->where('created_at', '<=', $cutoff)
            ->with([
                'admissionTests.test.materialRelation',
                'admissionTests.test.parentTests',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($admissions) use ($service, $drawer, &$updated, &$skipped) {
                foreach ($admissions as $admission) {
                    if (! $service->admissionRequiresSampleDraw($admission)) {
                        $skipped++;

                        continue;
                    }

                    $drawnAt = $admission->created_at
                        ?? Carbon::parse($admission->date)->startOfDay();

                    $admission->update([
                        'sample_drawn_at' => $drawnAt,
                        'sample_drawn_by' => $drawer->id,
                    ]);

                    $updated++;
                }
            });

        $this->command->info(
            "Backfill de extracciones: {$updated} admisiones asignadas a {$drawer->name} "
            ."(corte {$cutoff->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i')}). "
            ."{$skipped} admisiones omitidas (no requieren extracción)."
        );
    }

    private function resolveDefaultDrawer(): ?User
    {
        $configuredId = config('lab.sample_draw_backfill.user_id');
        if ($configuredId) {
            return User::query()->find($configuredId);
        }

        return User::query()
            ->where('name', 'like', '%Olie%')
            ->where('name', 'like', '%Clara%')
            ->first();
    }

    private function resolveCutoff(): Carbon
    {
        $configured = config('lab.sample_draw_backfill.before');
        if ($configured) {
            return Carbon::parse($configured);
        }

        return now();
    }
}
