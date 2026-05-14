<?php

namespace Tests\Unit;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Support\ClinicalPendingResultsPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalPendingResultsPresenterTest extends TestCase
{
    use RefreshDatabase;

    private function makePatient(): Patient
    {
        return Patient::query()->create([
            'name' => 'Ana',
            'lastName' => 'Test',
            'patientId' => '30111222',
            'type' => 'humano',
            'sex' => 'F',
            'status' => 'activo',
        ]);
    }

    private function makeTest(string $code, ?int $parent = null, int $sortOrder = 0, int $price = 0): Test
    {
        return Test::query()->create([
            'code' => $code,
            'name' => 'T '.$code,
            'unit' => 'g/L',
            'low' => null,
            'high' => null,
            'instructions' => null,
            'parent' => $parent,
            'decimals' => 2,
            'negative' => null,
            'positive' => null,
            'questions' => null,
            'method' => null,
            'price' => $price,
            'cost' => 0,
            'work_sheet' => null,
            'material' => null,
            'formula' => null,
            'box' => null,
            'nbu' => 1,
            'categories' => ['lab'],
            'sort_order' => $sortOrder,
        ]);
    }

    private function makeAdmission(User $user, Patient $patient, string $protocol = 'C-2026-PEND01'): Admission
    {
        return Admission::query()->create([
            'date' => now()->toDateString(),
            'patient_id' => $patient->id,
            'protocol_number' => $protocol,
            'number' => '1',
            'room' => 0,
            'institution' => 0,
            'invoice_date' => now()->toDateString(),
            'promise_date' => now()->toDateString(),
            'authorization_code' => '',
            'attended_by' => $user->id,
            'insurance_price' => 0,
            'patient_price' => 0,
            'cash' => 0,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_pending_label_muestra_nombre_del_padre_una_vez_con_dos_hijos(): void
    {
        $user = User::factory()->create();
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($user, $patient);

        $parent = $this->makeTest('PR-P', null, 1, 1000);
        $childA = $this->makeTest('PR-A', $parent->id, 2, 0);
        $childB = $this->makeTest('PR-B', $parent->id, 3, 0);

        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $parent->id,
            'price' => 100,
            'authorization_status' => 'not_required',
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $childA->id,
            'price' => 0,
            'result' => '10',
            'authorization_status' => 'not_required',
        ]);
        AdmissionTest::query()->create([
            'admission_id' => $admission->id,
            'test_id' => $childB->id,
            'price' => 0,
            'authorization_status' => 'not_required',
        ]);

        $admission->load(['admissionTests.test.parentTests']);
        $label = ClinicalPendingResultsPresenter::pendingDeterminationsLabel($admission, false);

        $this->assertSame('T PR-P', $label);
    }
}
