<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IngestResultsBatchRequest;
use App\Models\ApiClient;
use App\Services\Api\ApiResultIngestionService;
use Illuminate\Http\JsonResponse;

class ResultIngestionController extends Controller
{
    public function __construct(
        private readonly ApiResultIngestionService $service,
    ) {}

    public function batch(IngestResultsBatchRequest $request): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->get('api_client');

        $response = $this->service->process($client, $request->validated());

        return response()->json($response, 200);
    }
}
