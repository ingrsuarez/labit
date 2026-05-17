<?php

namespace App\Livewire\Vet;

use App\Actions\Vet\MutateVetAdmissionTest;
use App\Models\VetAdmission;
use App\Models\VetAdmissionTest;
use Livewire\Attributes\Locked;
use Livewire\Component;

class VetAdmissionResultsTable extends Component
{
    #[Locked]
    public int $vetAdmissionId;

    public bool $isRecepcionLab = false;

    public function mount(int $vetAdmissionId, bool $isRecepcionLab = false): void
    {
        $this->vetAdmissionId = $vetAdmissionId;
        $this->isRecepcionLab = $isRecepcionLab;
    }

    public function validateTest(int $vetAdmissionTestId): void
    {
        $vetAdmission = $this->vetAdmission();
        $test = $this->findTest($vetAdmission, $vetAdmissionTestId);
        $result = app(MutateVetAdmissionTest::class)->validate($vetAdmission, $test);
        $this->handleMutationResult($result);
    }

    public function unvalidateTest(int $vetAdmissionTestId): void
    {
        $vetAdmission = $this->vetAdmission();
        $test = $this->findTest($vetAdmission, $vetAdmissionTestId);
        $result = app(MutateVetAdmissionTest::class)->unvalidate($vetAdmission, $test);
        $this->handleMutationResult($result);
    }

    public function removeTest(int $vetAdmissionTestId): void
    {
        $vetAdmission = $this->vetAdmission();
        $test = $this->findTest($vetAdmission, $vetAdmissionTestId);
        $result = app(MutateVetAdmissionTest::class)->remove($vetAdmission, $test);
        $this->handleMutationResult($result);
    }

    public function render()
    {
        $vetAdmission = $this->vetAdmission();

        return view('livewire.vet.vet-admission-results-table', [
            'vetAdmission' => $vetAdmission,
            'orderedEntries' => $vetAdmission->getVetTestsOrderedForDisplay(),
        ]);
    }

    private function vetAdmission(): VetAdmission
    {
        return VetAdmission::query()
            ->with(['vetTests.test'])
            ->findOrFail($this->vetAdmissionId);
    }

    private function findTest(VetAdmission $vetAdmission, int $vetAdmissionTestId): VetAdmissionTest
    {
        $test = $vetAdmission->vetTests->firstWhere('id', $vetAdmissionTestId);
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
}
