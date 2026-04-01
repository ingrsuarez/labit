<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankStatement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BankStatementImporter
{
    public function import(BankAccount $bankAccount, UploadedFile $file, User $user): BankStatement
    {
        $spreadsheet = IOFactory::load($file->getPathname());

        $sheet = $this->selectBestSheet($spreadsheet);
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestDataColumn();
        $colCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $closingBalance = $this->parseBalance($sheet->getCell('B4')->getValue());
        $headerRow = $this->findHeaderRow($sheet, $highestRow);

        if ($headerRow === null) {
            throw new \RuntimeException('No se encontró la fila de encabezados en el archivo XLS.');
        }

        $hasDetail = $colCount >= 9;
        $movements = [];
        $totalCredits = 0;
        $totalDebits = 0;
        $dates = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $dateVal = trim((string) $sheet->getCell("A{$row}")->getValue());
            if (empty($dateVal) || ! preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateVal)) {
                continue;
            }

            $date = Carbon::createFromFormat('d-m-Y', $dateVal);
            $valueDateVal = trim((string) $sheet->getCell("B{$row}")->getValue());
            $valueDate = preg_match('/^\d{2}-\d{2}-\d{4}$/', $valueDateVal)
                ? Carbon::createFromFormat('d-m-Y', $valueDateVal)
                : null;

            $concept = trim((string) $sheet->getCell("C{$row}")->getValue());
            $bankCode = trim((string) $sheet->getCell("D{$row}")->getValue());
            $documentNumber = trim((string) $sheet->getCell("E{$row}")->getValue());
            $office = trim((string) $sheet->getCell("F{$row}")->getValue());

            $creditRaw = $sheet->getCell("G{$row}")->getValue();
            $debitRaw = $sheet->getCell("H{$row}")->getValue();

            $credit = max(0, $this->parseAmount($creditRaw));
            $debit = abs(min(0, $this->parseAmount($debitRaw)));

            $balanceCol = $hasDetail ? 'J' : 'I';
            $balanceRaw = $sheet->getCell("{$balanceCol}{$row}")->getValue();
            if (empty($balanceRaw) || ! is_numeric($balanceRaw)) {
                $balanceCol = $hasDetail ? 'I' : 'I';
                $balanceRaw = $sheet->getCell("{$balanceCol}{$row}")->getValue();
            }
            $balance = is_numeric($balanceRaw) ? (float) $balanceRaw : null;

            $detail = $hasDetail ? trim((string) $sheet->getCell("I{$row}")->getValue()) : null;

            $category = BankMovement::categorize($concept, $bankCode ?: null);

            $movements[] = [
                'date' => $date->format('Y-m-d'),
                'value_date' => $valueDate?->format('Y-m-d'),
                'concept' => $concept,
                'bank_code' => $bankCode ?: null,
                'document_number' => $documentNumber ?: null,
                'office' => $office ?: null,
                'credit' => $credit,
                'debit' => $debit,
                'balance' => $balance,
                'detail' => $detail ?: null,
                'category' => $category,
                'reconciliation_status' => 'pending',
            ];

            $totalCredits += $credit;
            $totalDebits += $debit;
            $dates[] = $date;
        }

        if (empty($movements)) {
            throw new \RuntimeException('No se encontraron movimientos válidos en el archivo.');
        }

        $periodFrom = collect($dates)->min();
        $periodTo = collect($dates)->max();

        $existing = BankStatement::where('bank_account_id', $bankAccount->id)
            ->where('period_from', $periodFrom->format('Y-m-d'))
            ->where('period_to', $periodTo->format('Y-m-d'))
            ->where('filename', $file->getClientOriginalName())
            ->first();

        if ($existing) {
            throw new \RuntimeException("Ya existe un extracto importado para este período ({$periodFrom->format('d/m/Y')} - {$periodTo->format('d/m/Y')}) con el mismo archivo.");
        }

        return DB::transaction(function () use ($bankAccount, $file, $user, $movements, $periodFrom, $periodTo, $closingBalance, $totalCredits, $totalDebits) {
            $statement = BankStatement::create([
                'bank_account_id' => $bankAccount->id,
                'period_from' => $periodFrom->format('Y-m-d'),
                'period_to' => $periodTo->format('Y-m-d'),
                'closing_balance' => $closingBalance,
                'total_credits' => $totalCredits,
                'total_debits' => $totalDebits,
                'movements_count' => count($movements),
                'filename' => $file->getClientOriginalName(),
                'imported_by' => $user->id,
                'imported_at' => now(),
                'status' => 'draft',
            ]);

            foreach ($movements as $movement) {
                $statement->movements()->create($movement);
            }

            return $statement;
        });
    }

    private function selectBestSheet($spreadsheet): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        foreach ($spreadsheet->getSheetNames() as $index => $name) {
            if (str_contains(mb_strtolower($name), 'hist')) {
                return $spreadsheet->getSheet($index);
            }
        }

        $bestSheet = $spreadsheet->getSheet(0);
        $bestRows = $bestSheet->getHighestRow();

        for ($i = 1; $i < $spreadsheet->getSheetCount(); $i++) {
            $sheet = $spreadsheet->getSheet($i);
            if ($sheet->getHighestRow() > $bestRows) {
                $bestSheet = $sheet;
                $bestRows = $sheet->getHighestRow();
            }
        }

        return $bestSheet;
    }

    private function findHeaderRow($sheet, int $maxRow): ?int
    {
        for ($row = 1; $row <= min($maxRow, 15); $row++) {
            $val = trim((string) $sheet->getCell("A{$row}")->getValue());
            if (mb_strtolower($val) === 'fecha') {
                return $row;
            }
        }

        return null;
    }

    private function parseBalance($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $cleaned = str_replace(['.', ','], ['', '.'], trim($value));
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        return null;
    }

    private function parseAmount($value): float
    {
        if (empty($value) && $value !== 0 && $value !== '0' && $value !== 0.0) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = str_replace(['.', ','], ['', '.'], trim((string) $value));

        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }
}
