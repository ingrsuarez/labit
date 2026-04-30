<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\SalesInvoice;
use App\Services\AfipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix one-shot para resolver la duplicacion de la empresa "Olie Clara Silvina"
 * en produccion, donde quedaron dos registros por formato distinto del CUIT:
 *
 *   A) cuit='27291450348'    (sin guiones, la que ya tenia 17 facturas y POS 00006)
 *   B) cuit='27-29145034-8'  (con guiones, creada por error con la migracion AFIP)
 *
 * Migra los certificados AFIP y el POS 00007 de B → A, normaliza el CUIT de A
 * al formato con guiones, y borra B. Despues prueba la conexion con AFIP.
 *
 * Modo de uso (en produccion):
 *   php artisan afip:fix-olie-duplicada              # dry-run, no toca nada
 *   php artisan afip:fix-olie-duplicada --apply      # aplica los cambios
 *
 * Este comando es one-shot. Una vez aplicado en produccion puede borrarse
 * en un commit posterior, queda en el historial como referencia.
 */
class FixOlieDuplicada extends Command
{
    protected $signature = 'afip:fix-olie-duplicada
                            {--apply : Aplica los cambios. Sin esta opcion solo muestra el diagnostico}';

    protected $description = 'Migra cert+POS de la empresa Olie duplicada (CUIT 27-29145034-8) a la original (27291450348) y borra la duplicada';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('Modo: '.($apply ? 'APPLY (escribira en BD)' : 'CHECK (dry-run)'));
        $this->newLine();

        $a = Company::where('cuit', '27291450348')->first();
        $b = Company::where('cuit', '27-29145034-8')->first();

        if (! $a) {
            $this->error('No se encontro la empresa A (CUIT 27291450348). Abortando.');

            return self::FAILURE;
        }

        if (! $b) {
            $this->warn('No se encontro la empresa B (CUIT 27-29145034-8). Quizas ya se borro.');

            if ($a->afip_cert_path) {
                $this->info('Empresa A ya tiene cert configurado. Nada que hacer.');

                return self::SUCCESS;
            }

            $this->error('Empresa A no tiene cert configurado. Faltan datos para migrar.');

            return self::FAILURE;
        }

        $this->printCompany('Empresa A (la real, conservar)', $a);
        $this->printCompany('Empresa B (duplicada, borrar)', $b);

        if (SalesInvoice::where('company_id', $b->id)->count() > 0) {
            $this->error('ABORTAR: la empresa B tiene facturas. No se puede borrar sin migrar.');

            return self::FAILURE;
        }

        $pos7B = PointOfSale::where('company_id', $b->id)->where('afip_pos_number', 7)->first();
        $pos7A = PointOfSale::where('company_id', $a->id)->where('afip_pos_number', 7)->first();

        $this->info('Acciones a ejecutar:');
        $this->line('  1) Copiar afip_cert_path / afip_key_path / afip_production de B → A');
        $this->line("  2) Normalizar CUIT de A: '27291450348' → '27-29145034-8'");

        if ($pos7B && ! $pos7A) {
            $this->line("  3) Mover POS id={$pos7B->id} (code={$pos7B->code}) de empresa B → A");
        } elseif ($pos7A && $pos7B) {
            $this->line("  3) Empresa A ya tiene POS 7 propio (id={$pos7A->id}); borrar el de B id={$pos7B->id}");
        } else {
            $this->line('  3) (skip) No hay POS 7 que mover');
        }

        $this->line('  4) Detach usuarios de B (si los hubiera)');
        $this->line('  5) Borrar empresa B');
        $this->newLine();

        if (! $apply) {
            $this->warn('Dry-run. Volve a correr con --apply para ejecutar los cambios.');

            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $a->afip_cert_path = $b->afip_cert_path;
            $a->afip_key_path = $b->afip_key_path;
            $a->afip_production = $b->afip_production;
            if (! $a->short_name) {
                $a->short_name = $b->short_name ?: 'Olie Unipersonal';
            }
            $a->cuit = '27-29145034-8';
            $a->save();
            $this->info('[1+2] Empresa A actualizada con certs y CUIT normalizado.');

            if ($pos7B && ! $pos7A) {
                $pos7B->company_id = $a->id;
                $pos7B->save();
                $this->info("[3] POS id={$pos7B->id} reasignado a empresa A (id={$a->id}).");
            } elseif ($pos7A && $pos7B) {
                $pos7B->delete();
                $this->info("[3] POS duplicado de B id={$pos7B->id} borrado (A ya tenia el suyo id={$pos7A->id}).");
            }

            if ($b->users()->count() > 0) {
                $b->users()->detach();
                $this->info('[4] Usuarios desvinculados de B.');
            }

            $b->delete();
            $this->info('[5] Empresa B borrada.');

            $taFile = storage_path('app/afip/ta_wsfe_27291450348_prod.json');
            if (file_exists($taFile)) {
                @unlink($taFile);
                $this->info('[bonus] Token AFIP cacheado borrado para refresh limpio.');
            }

            DB::commit();
            $this->newLine();
            $this->info('>>> MIGRACION COMPLETADA OK');
            $this->newLine();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('ERROR: '.$e->getMessage());
            $this->error('Rollback ejecutado.');

            return self::FAILURE;
        }

        $this->verifyPostFix($a);

        return self::SUCCESS;
    }

    protected function printCompany(string $label, Company $c): void
    {
        $posCount = PointOfSale::where('company_id', $c->id)->count();
        $invCount = SalesInvoice::where('company_id', $c->id)->count();
        $userCount = $c->users()->count();

        $this->line($label.':');
        $this->line("  id={$c->id}  cuit={$c->cuit}  name={$c->name}  short={$c->short_name}");
        $this->line("  cert={$c->afip_cert_path}  key={$c->afip_key_path}  prod=".($c->afip_production ? '1' : '0'));
        $this->line("  POS: {$posCount}, facturas: {$invCount}, usuarios: {$userCount}");
        $this->newLine();
    }

    protected function verifyPostFix(Company $a): void
    {
        $a->refresh();
        $this->info('=== Verificacion post-fix ===');
        $this->line("Empresa final: id={$a->id} cuit={$a->cuit} name={$a->name}");
        $this->line("Cert: {$a->afip_cert_path} (existe: ".(file_exists(base_path($a->afip_cert_path)) ? 'SI' : 'NO').')');
        $this->line("Key : {$a->afip_key_path} (existe: ".(file_exists(base_path($a->afip_key_path)) ? 'SI' : 'NO').')');

        $this->line('POS de la empresa:');
        foreach (PointOfSale::where('company_id', $a->id)->get() as $p) {
            $this->line("  id={$p->id}  code={$p->code}  name={$p->name}  pos_afip={$p->afip_pos_number}  electronico=".($p->is_electronic ? 'SI' : 'NO'));
        }

        $this->newLine();
        $this->info('Test conexion AFIP:');
        try {
            $svc = new AfipService($a);
            $status = $svc->getServerStatus();
            $this->line('FEDummy: '.json_encode($status));
            $this->line('Last Factura B POS 7: '.$svc->getLastVoucher(7, 6));
            $this->line('Last Factura C POS 7: '.$svc->getLastVoucher(7, 11));
            $this->newLine();
            $this->info('>>> TODO OK. Empresa lista para facturar electronicamente.');
        } catch (\Throwable $e) {
            $this->error('ERROR test AFIP: '.$e->getMessage());
        }
    }
}
