<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Services\AdmissionSampleDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabSampleDrawController extends Controller
{
    public function __construct(
        private AdmissionSampleDrawService $sampleDrawService
    ) {}

    public function pendingCount(): JsonResponse
    {
        $this->authorize('lab-sample-draws.view');

        $branchId = active_lab_branch_id();

        return response()->json([
            'count' => $this->sampleDrawService->pendingCount($branchId),
            'lab_branch_id' => $branchId,
        ]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('lab-sample-draws.view');

        $branchId = active_lab_branch_id();

        return response()->json([
            'items' => $this->sampleDrawService->listPending($branchId),
            'drawers' => $this->sampleDrawService->eligibleDrawers()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]),
            'must_select_drawer' => auth()->user()->hasAnyRole(['recepcion-lab', 'admin'])
                && ! auth()->user()->hasAnyRole(['tecnico-lab', 'bioquimico']),
        ]);
    }

    public function register(Request $request, Admission $admission): JsonResponse
    {
        $this->authorize('lab-sample-draws.register');

        $drawerId = $this->sampleDrawService->resolveDrawerUserId(
            $request->input('sample_drawn_by') ? (int) $request->input('sample_drawn_by') : null,
            $request->user()
        );

        $this->sampleDrawService->registerDraw($admission, $drawerId);

        return response()->json(['ok' => true]);
    }
}
