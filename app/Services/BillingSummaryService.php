<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Customer;
use App\Models\Insurance;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BillingSummaryService
{
    public function __construct(
        protected BillingSummaryCodeResolver $codeResolver,
    ) {}

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array{protocol_count: int, total_amount: float}}
     */
    public function buildClinicalRows(Insurance $insurance, Carbon $from, Carbon $to): array
    {
        $admissions = Admission::query()
            ->with(['patient', 'admissionTests.test.parentTests'])
            ->where('insurance', $insurance->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $admissions->map(function (Admission $admission) {
            $resolved = $this->codeResolver->resolve(
                $admission->admissionTests,
                fn (AdmissionTest $at) => $at->test,
                fn (AdmissionTest $at) => (int) $at->test_id,
                fn (AdmissionTest $at) => ! $at->paid_by_patient
                    && $at->authorization_status !== AdmissionTest::STATUS_REJECTED,
                fn (AdmissionTest $at) => (float) $at->price - (float) $at->copago,
            );

            return [
                'date' => $admission->date,
                'formatted_date' => Carbon::parse($admission->date)->format('d/m/Y'),
                'name' => $admission->patient?->full_name ?? 'N/A',
                'dni' => $admission->patient?->patientId ?? 'N/A',
                'affiliate' => $admission->affiliate_number ?: 'N/A',
                'codes' => $resolved['codes_string'],
                'price' => $resolved['total_amount'],
                'protocol_number' => $admission->protocol_number,
            ];
        })->filter(fn (array $row) => $row['codes'] !== '' || $row['price'] > 0)->values();

        return [
            'rows' => $rows,
            'totals' => $this->totalsFromRows($rows),
        ];
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array{protocol_count: int, total_amount: float}}
     */
    public function buildSampleRows(Customer $customer, Carbon $from, Carbon $to): array
    {
        $samples = Sample::query()
            ->with(['determinations.test.parentTests'])
            ->where('customer_id', $customer->id)
            ->whereDate('entry_date', '>=', $from->toDateString())
            ->whereDate('entry_date', '<=', $to->toDateString())
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $rows = $samples->map(function (Sample $sample) {
            $resolved = $this->codeResolver->resolve(
                $sample->determinations,
                fn (SampleDetermination $d) => $d->test,
                fn (SampleDetermination $d) => (int) $d->test_id,
                fn () => true,
                fn (SampleDetermination $d) => (float) $d->price,
            );

            $name = $sample->location
                ?: ($sample->product_name ?: $sample->protocol_number);

            return [
                'date' => $sample->entry_date,
                'formatted_date' => $sample->entry_date?->format('d/m/Y') ?? '',
                'name' => $name,
                'codes' => $resolved['codes_string'],
                'price' => $resolved['total_amount'],
                'protocol_number' => $sample->protocol_number,
            ];
        })->filter(fn (array $row) => $row['codes'] !== '' || $row['price'] > 0)->values();

        return [
            'rows' => $rows,
            'totals' => $this->totalsFromRows($rows),
        ];
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array{protocol_count: int, total_amount: float}}
     */
    public function buildVetRows(Customer $customer, Carbon $from, Carbon $to): array
    {
        $admissions = VetAdmission::query()
            ->with(['vetTests.test.parentTests'])
            ->where('customer_id', $customer->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $admissions->map(function (VetAdmission $admission) {
            $resolved = $this->codeResolver->resolve(
                $admission->vetTests,
                fn (VetAdmissionTest $vt) => $vt->test,
                fn (VetAdmissionTest $vt) => (int) $vt->test_id,
                fn () => true,
                fn (VetAdmissionTest $vt) => (float) $vt->price,
            );

            return [
                'date' => $admission->date,
                'formatted_date' => Carbon::parse($admission->date)->format('d/m/Y'),
                'name' => $admission->animal_name ?? 'N/A',
                'codes' => $resolved['codes_string'],
                'price' => $resolved['total_amount'],
                'protocol_number' => $admission->protocol_number,
            ];
        })->filter(fn (array $row) => $row['codes'] !== '' || $row['price'] > 0)->values();

        return [
            'rows' => $rows,
            'totals' => $this->totalsFromRows($rows),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{protocol_count: int, total_amount: float}
     */
    public function totalsFromRows(Collection $rows): array
    {
        return [
            'protocol_count' => $rows->count(),
            'total_amount' => round((float) $rows->sum('price'), 2),
        ];
    }

    public function normalizeFormat(?string $format): string
    {
        return in_array($format, ['summary', 'detailed'], true) ? $format : 'summary';
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public function buildClinical(Insurance $insurance, Carbon $from, Carbon $to, string $format = 'summary'): array
    {
        return $this->normalizeFormat($format) === 'detailed'
            ? $this->buildClinicalDetailedRows($insurance, $from, $to)
            : $this->buildClinicalRows($insurance, $from, $to);
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public function buildSample(Customer $customer, Carbon $from, Carbon $to, string $format = 'summary'): array
    {
        return $this->normalizeFormat($format) === 'detailed'
            ? $this->buildSampleDetailedRows($customer, $from, $to)
            : $this->buildSampleRows($customer, $from, $to);
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public function buildVet(Customer $customer, Carbon $from, Carbon $to, string $format = 'summary'): array
    {
        return $this->normalizeFormat($format) === 'detailed'
            ? $this->buildVetDetailedRows($customer, $from, $to)
            : $this->buildVetRows($customer, $from, $to);
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array{protocol_count: int, line_count: int, total_amount: float}}
     */
    public function buildClinicalDetailedRows(Insurance $insurance, Carbon $from, Carbon $to): array
    {
        $admissions = Admission::query()
            ->with(['patient', 'admissionTests.test.parentTests'])
            ->where('insurance', $insurance->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = collect();

        foreach ($admissions as $admission) {
            $resolved = $this->codeResolver->resolve(
                $admission->admissionTests,
                fn (AdmissionTest $at) => $at->test,
                fn (AdmissionTest $at) => (int) $at->test_id,
                fn (AdmissionTest $at) => ! $at->paid_by_patient
                    && $at->authorization_status !== AdmissionTest::STATUS_REJECTED,
                fn (AdmissionTest $at) => (float) $at->price - (float) $at->copago,
            );

            $isFirst = true;
            foreach ($resolved['included'] as $line) {
                $test = $line->test;
                $rows->push([
                    'formatted_date' => $isFirst ? Carbon::parse($admission->date)->format('d/m/Y') : '',
                    'patient_label' => $isFirst ? $this->formatClinicalPatientLabel($admission) : '',
                    'dni' => $isFirst ? ($admission->patient?->patientId ?? 'N/A') : '',
                    'code' => $test?->code ?? '',
                    'practice' => $test?->name ?? '',
                    'amount' => (float) $line->price - (float) $line->copago,
                    'protocol_number' => $admission->protocol_number,
                ]);
                $isFirst = false;
            }
        }

        return [
            'rows' => $rows,
            'totals' => $this->totalsFromDetailedRows($rows),
        ];
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array{protocol_count: int, line_count: int, total_amount: float}}
     */
    public function buildSampleDetailedRows(Customer $customer, Carbon $from, Carbon $to): array
    {
        $samples = Sample::query()
            ->with(['determinations.test.parentTests'])
            ->where('customer_id', $customer->id)
            ->whereDate('entry_date', '>=', $from->toDateString())
            ->whereDate('entry_date', '<=', $to->toDateString())
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $rows = collect();

        foreach ($samples as $sample) {
            $resolved = $this->codeResolver->resolve(
                $sample->determinations,
                fn (SampleDetermination $d) => $d->test,
                fn (SampleDetermination $d) => (int) $d->test_id,
                fn () => true,
                fn (SampleDetermination $d) => (float) $d->price,
            );

            $subject = $sample->location ?: ($sample->product_name ?: $sample->protocol_number);
            $isFirst = true;

            foreach ($resolved['included'] as $line) {
                $test = $line->test;
                $rows->push([
                    'formatted_date' => $isFirst ? ($sample->entry_date?->format('d/m/Y') ?? '') : '',
                    'subject_label' => $isFirst ? $subject : '',
                    'code' => $test?->code ?? '',
                    'practice' => $test?->name ?? '',
                    'amount' => (float) $line->price,
                    'protocol_number' => $sample->protocol_number,
                ]);
                $isFirst = false;
            }
        }

        return [
            'rows' => $rows,
            'totals' => $this->totalsFromDetailedRows($rows),
        ];
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array{protocol_count: int, line_count: int, total_amount: float}}
     */
    public function buildVetDetailedRows(Customer $customer, Carbon $from, Carbon $to): array
    {
        $admissions = VetAdmission::query()
            ->with(['vetTests.test.parentTests'])
            ->where('customer_id', $customer->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = collect();

        foreach ($admissions as $admission) {
            $resolved = $this->codeResolver->resolve(
                $admission->vetTests,
                fn (VetAdmissionTest $vt) => $vt->test,
                fn (VetAdmissionTest $vt) => (int) $vt->test_id,
                fn () => true,
                fn (VetAdmissionTest $vt) => (float) $vt->price,
            );

            $isFirst = true;

            foreach ($resolved['included'] as $line) {
                $test = $line->test;
                $rows->push([
                    'formatted_date' => $isFirst ? Carbon::parse($admission->date)->format('d/m/Y') : '',
                    'subject_label' => $isFirst ? ($admission->animal_name ?? 'N/A') : '',
                    'code' => $test?->code ?? '',
                    'practice' => $test?->name ?? '',
                    'amount' => (float) $line->price,
                    'protocol_number' => $admission->protocol_number,
                ]);
                $isFirst = false;
            }
        }

        return [
            'rows' => $rows,
            'totals' => $this->totalsFromDetailedRows($rows),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{protocol_count: int, line_count: int, total_amount: float}
     */
    public function totalsFromDetailedRows(Collection $rows): array
    {
        return [
            'protocol_count' => $rows->pluck('protocol_number')->unique()->filter()->count(),
            'line_count' => $rows->count(),
            'total_amount' => round((float) $rows->sum('amount'), 2),
        ];
    }

    protected function formatClinicalPatientLabel(Admission $admission): string
    {
        $patient = $admission->patient;
        if (! $patient) {
            return 'N/A';
        }

        $name = trim($patient->lastName.', '.$patient->name);
        $affiliate = $admission->affiliate_number ?: '';

        return $affiliate !== '' ? $name."\n".$affiliate : $name;
    }

    public function parseDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom
            ? Carbon::parse($dateFrom)->startOfDay()
            : now()->startOfMonth();
        $to = $dateTo
            ? Carbon::parse($dateTo)->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
