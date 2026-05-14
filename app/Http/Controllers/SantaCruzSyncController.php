<?php

namespace App\Http\Controllers;

use App\Contracts\SantaCruzFtpClientInterface;
use App\Models\SantaCruzTestMapping;
use App\Services\SantaCruz\SantaCruzImportService;
use App\Services\SantaCruz\SantaCruzXmlParseException;
use App\Services\SantaCruz\SantaCruzXmlParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SantaCruzSyncController extends Controller
{
    public function sync(Request $request): View
    {
        $this->authorize('santacruz.import');

        $scan = session('santa_cruz_scan');
        $rows = is_array($scan) ? ($scan['rows'] ?? []) : [];
        $insuranceId = config('santacruz.insurance_id');

        return view('lab.santa-cruz.sync', [
            'rows' => $rows,
            'insuranceId' => $insuranceId,
            'imported' => session('santa_cruz_imported', []),
        ]);
    }

    public function scan(
        Request $request,
        SantaCruzFtpClientInterface $ftp,
        SantaCruzXmlParser $parser,
        SantaCruzImportService $importService,
    ): RedirectResponse {
        $this->authorize('santacruz.import');

        @set_time_limit((int) config('santacruz.scan_max_execution_seconds', 900));

        $insuranceId = config('santacruz.insurance_id');
        if (! $insuranceId) {
            return redirect()
                ->route('lab.santa-cruz.sync')
                ->with('error', 'Falta configurar SANTA_CRUZ_INSURANCE_ID en .env (obra social Santa Cruz en Labit).');
        }

        try {
            $files = $ftp->listXmlFiles();
        } catch (Throwable $e) {
            return redirect()
                ->route('lab.santa-cruz.sync')
                ->with('error', 'FTP: '.$e->getMessage());
        }

        $rows = [];
        foreach ($files as $file) {
            try {
                $xml = $ftp->getFileContents($file);
                $parsed = $parser->parse($xml);
                $resolved = $importService->resolvePracticas($parsed['practicas']);
                $ready = collect($resolved)->every(fn ($p) => $p['mapped']);
                $rows[] = [
                    'file' => $file,
                    'error' => null,
                    'parsed' => $parsed,
                    'practicas_resolved' => $resolved,
                    'ready' => $ready,
                ];
            } catch (SantaCruzXmlParseException $e) {
                $rows[] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                    'parsed' => null,
                    'practicas_resolved' => [],
                    'ready' => false,
                ];
            } catch (Throwable $e) {
                $rows[] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                    'parsed' => null,
                    'practicas_resolved' => [],
                    'ready' => false,
                ];
            }
        }

        session([
            'santa_cruz_scan' => [
                'rows' => $rows,
                'insurance_id' => $insuranceId,
            ],
        ]);

        if (count($rows) === 0) {
            return redirect()
                ->route('lab.santa-cruz.sync')
                ->with('warning', 'No hay archivos .xml en la carpeta FTP configurada.');
        }

        return redirect()
            ->route('lab.santa-cruz.sync')
            ->with('success', 'Sincronización: '.count($rows).' archivo(s) leídos desde el FTP.');
    }

    public function import(
        Request $request,
        SantaCruzFtpClientInterface $ftp,
        SantaCruzXmlParser $parser,
        SantaCruzImportService $importService,
    ): RedirectResponse {
        $this->authorize('santacruz.import');

        @set_time_limit((int) config('santacruz.scan_max_execution_seconds', 900));

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|string|max:255',
        ]);

        $insuranceId = config('santacruz.insurance_id');
        if (! $insuranceId) {
            return redirect()->route('lab.santa-cruz.sync')->with('error', 'Falta SANTA_CRUZ_INSURANCE_ID.');
        }

        $scan = session('santa_cruz_scan');
        $rows = is_array($scan) ? ($scan['rows'] ?? []) : [];
        $byFile = collect($rows)->keyBy('file');

        $imported = [];
        $warnings = [];

        foreach ($request->input('files', []) as $basename) {
            $row = $byFile->get($basename);
            if (! $row || ! empty($row['error']) || empty($row['parsed'])) {
                return redirect()->route('lab.santa-cruz.sync')->with('error', 'Vuelva a sincronizar: datos desactualizados para '.$basename);
            }
            if (! $row['ready']) {
                return redirect()->route('lab.santa-cruz.sync')->with('error', 'Hay prácticas sin mapear en '.$basename.'.');
            }

            $parsed = $row['parsed'];
            $testIds = collect($row['practicas_resolved'])->pluck('test_id')->map(fn ($id) => (int) $id)->all();

            try {
                $xml = $ftp->getFileContents($basename);
                $parsedFresh = $parser->parse($xml);
            } catch (Throwable $e) {
                return redirect()->route('lab.santa-cruz.sync')->with('error', 'No se pudo releer el XML: '.$e->getMessage());
            }

            if ($parsedFresh['document_number'] !== $parsed['document_number']
                || $parsedFresh['accession_number'] !== $parsed['accession_number']) {
                return redirect()->route('lab.santa-cruz.sync')->with('error', 'El archivo '.$basename.' cambió en el servidor desde la última vista previa.');
            }

            $labBranchId = active_lab_branch_id();

            try {
                $admission = $importService->importAdmission(
                    $parsedFresh,
                    $testIds,
                    (int) $insuranceId,
                    $labBranchId ? (int) $labBranchId : null,
                    (int) $request->user()->id,
                );
            } catch (Throwable $e) {
                return redirect()->route('lab.santa-cruz.sync')->with('error', 'Error al importar '.$basename.': '.$e->getMessage());
            }

            try {
                $ftp->moveToProcessed($basename);
            } catch (Throwable $e) {
                $warnings[] = 'Admisión '.$admission->protocol_number.' creada, pero no se movió el XML en el FTP: '.$e->getMessage();
            }

            $imported[] = [
                'file' => $basename,
                'admission_id' => $admission->id,
                'protocol_number' => $admission->protocol_number,
            ];
        }

        session()->forget('santa_cruz_scan');

        $redirect = redirect()->route('lab.santa-cruz.sync')
            ->with('success', 'Se importaron '.count($imported).' admisión(es).')
            ->with('santa_cruz_imported', $imported);

        if ($warnings !== []) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        $this->authorize('santacruz.import');

        $validated = $request->validate([
            'prestacion_code' => 'required|string|max:64',
            'prestacion_name' => 'nullable|string|max:255',
            'test_id' => 'required|exists:tests,id',
        ]);

        $norm = SantaCruzTestMapping::normalizePrestacionCode($validated['prestacion_code']);

        SantaCruzTestMapping::updateOrCreate(
            ['prestacion_code' => $norm],
            [
                'prestacion_name' => $validated['prestacion_name'] ?? null,
                'test_id' => (int) $validated['test_id'],
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'Mapeo guardado para el código '.$validated['prestacion_code'].'. Sincronizá de nuevo el FTP para refrescar la vista previa.');
    }

    public function mappingsIndex(): View
    {
        $this->authorize('santacruz.import');

        $mappings = SantaCruzTestMapping::query()
            ->with('test')
            ->orderBy('prestacion_code')
            ->paginate(30);

        return view('lab.santa-cruz.mappings-index', compact('mappings'));
    }
}
