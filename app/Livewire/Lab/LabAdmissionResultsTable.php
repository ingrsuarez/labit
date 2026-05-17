<?php

namespace App\Livewire\Lab;

use App\Actions\Lab\MutateAdmissionTest;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Support\ClinicalAdmissionResultsOrdering;
use App\Support\ProtocolReferenceDisplay;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LabAdmissionResultsTable extends Component
{
    #[Locked]
    public int $admissionId;

    public bool $isRecepcionLab = false;

    public function mount(int $admissionId, bool $isRecepcionLab = false): void
    {
        $this->admissionId = $admissionId;
        $this->isRecepcionLab = $isRecepcionLab;
    }

    public function validateTest(int $admissionTestId): void
    {
        $this->authorize('lab-results.validate');
        $admission = $this->admission();
        $test = $this->findTest($admission, $admissionTestId);
        $result = app(MutateAdmissionTest::class)->validate($admission, $test);
        $this->handleMutationResult($result);
    }

    public function unvalidateTest(int $admissionTestId): void
    {
        $this->authorize('lab-results.validate');
        $admission = $this->admission();
        $test = $this->findTest($admission, $admissionTestId);
        $result = app(MutateAdmissionTest::class)->unvalidate($admission, $test);
        $this->handleMutationResult($result);
    }

    public function removeTest(int $admissionTestId): void
    {
        $this->authorize('lab-admissions.delete');
        $admission = $this->admission();
        $test = $this->findTest($admission, $admissionTestId);
        $result = app(MutateAdmissionTest::class)->remove($admission, $test);
        $this->handleMutationResult($result);
    }

    public function render()
    {
        $admission = $this->admission();
        $ordering = ClinicalAdmissionResultsOrdering::build($admission);

        return view('livewire.lab.lab-admission-results-table', [
            'admission' => $admission,
            'orderedItems' => $ordering['items'],
            'parentMap' => $ordering['parentMap'],
            'childOf' => $ordering['childOf'],
            'canEditResults' => auth()->user()->can('lab-results.create'),
            'canValidate' => auth()->user()->can('lab-results.validate'),
        ]);
    }

    private function admission(): Admission
    {
        return Admission::query()
            ->with([
                'admissionTests.test.parentTests',
                'admissionTests.test.referenceValues',
                'admissionTests.test.children',
                'admissionTests.test.childTests',
            ])
            ->findOrFail($this->admissionId);
    }

    private function findTest(Admission $admission, int $admissionTestId): AdmissionTest
    {
        $test = $admission->admissionTests->firstWhere('id', $admissionTestId);
        if (! $test) {
            abort(404);
        }

        return $test;
    }

    /**
     * @param  array{ok: bool, message: string}  $result
     */
    private function handleMutationResult(array $result): void
    {
        if (! $result['ok']) {
            $this->dispatch('notify', message: $result['message'], type: 'error');

            return;
        }

        $this->dispatch('notify', message: $result['message'], type: 'success');
    }

    public static function referenceLineForTest(AdmissionTest $admissionTest): string
    {
        $test = $admissionTest->test;
        $primaryRef = null;
        $defaultRef = $test->referenceValues->where('is_default', true)->first();
        if ($defaultRef) {
            if ($defaultRef->min_value !== null && $defaultRef->max_value !== null) {
                $primaryRef = $defaultRef->min_value.' - '.$defaultRef->max_value;
            } elseif ($defaultRef->value) {
                $primaryRef = $defaultRef->value;
            }
        } elseif ($test->low !== null && $test->high !== null) {
            $primaryRef = $test->low.' - '.$test->high;
        }

        return ProtocolReferenceDisplay::line($primaryRef, $test->other_reference ?? null);
    }
}
