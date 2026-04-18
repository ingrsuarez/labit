<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ProtocolType;
use App\Models\Admission;
use App\Models\ApiClient;
use App\Models\Sample;
use App\Models\VetAdmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Resource polimórfico que unifica los 3 modelos de protocolo
 * (`Admission`, `Sample`, `VetAdmission`) en una sola estructura JSON
 * consumible por LISCOM.
 *
 * El `ApiClient` viene inyectado en la request por el middleware
 * `auth.api_key` (v1.46.0). Lo usamos para decidir si exponemos PII
 * sensible (DNI) según `patient_data_level`.
 */
class ProtocolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->resolveType();
        $client = $request->get('api_client');

        $dateValue = $this->resource instanceof Sample
            ? $this->resource->entry_date
            : $this->resource->date;

        return [
            'id' => $this->id,
            'type' => $type->value,
            'protocol_number' => $this->protocol_number,
            // En v1.47.0 el barcode == protocol_number. La discusión sobre
            // un barcode extendido (con material) está aplazada para v1.48.5.
            'barcode' => $this->protocol_number,
            'date' => $dateValue ? Carbon::parse($dateValue)->toDateString() : null,
            'status' => $this->status,
            'lab_branch' => $this->resource->labBranch ? [
                'id' => $this->resource->labBranch->id,
                'name' => $this->resource->labBranch->name,
            ] : null,
            'patient' => $this->buildPatientData($client),
            'determinations' => DeterminationResource::collection($this->getDeterminationsRelation()),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => "/api/v1/protocols/{$type->value}/{$this->id}",
            ],
        ];
    }

    private function resolveType(): ProtocolType
    {
        return match (true) {
            $this->resource instanceof Admission => ProtocolType::Clinical,
            $this->resource instanceof Sample => ProtocolType::Sample,
            $this->resource instanceof VetAdmission => ProtocolType::Vet,
        };
    }

    /**
     * Datos del "paciente" en sentido amplio.
     * - clinical: Patient real (nombre/apellido/sexo/edad)
     * - sample: cliente + campos específicos (location/batch/product_name);
     *           sin paciente humano porque las muestras de aguas/alimentos
     *           no tienen una persona física asociada.
     * - vet: dueño + animal (nombre/edad/raza/especie); el animal NO es un
     *        modelo separado en este proyecto, sus datos están en la
     *        misma fila de VetAdmission.
     *
     * El DNI del paciente humano se omite salvo que la API key tenga
     * `patient_data_level=standard` (decisión PM: minimizar PII).
     */
    private function buildPatientData(ApiClient $client): array
    {
        if ($this->resource instanceof Sample) {
            return [
                'id' => $this->resource->customer_id,
                'display_name' => $this->resource->customer?->name ?? 'Cliente desconocido',
                'sex' => null,
                'age_years' => null,
                'species' => null,
                'breed' => null,
                'animal_name' => null,
                'document' => null,
                'location' => $this->resource->location,
                'batch' => $this->resource->batch,
                'product_name' => $this->resource->product_name,
            ];
        }

        if ($this->resource instanceof Admission) {
            $patient = $this->resource->patient;

            return [
                'id' => $patient?->id,
                'display_name' => $patient ? trim("{$patient->lastName}, {$patient->name}") : null,
                'sex' => $patient?->sex,
                'age_years' => $patient?->birth ? Carbon::parse($patient->birth)->age : null,
                'species' => null,
                'breed' => null,
                'animal_name' => null,
                'document' => $client->includesDni() ? $patient?->patientId : null,
            ];
        }

        if ($this->resource instanceof VetAdmission) {
            $vet = $this->resource;

            return [
                'id' => $vet->id,
                'display_name' => trim(($vet->customer?->name ?? $vet->owner_name ?? '').' / '.($vet->animal_name ?? '')),
                'sex' => null,
                'age_years' => $vet->age,
                'species' => $vet->species?->name,
                'breed' => $vet->breed,
                'animal_name' => $vet->animal_name,
                'document' => $client->includesDni() ? ($vet->customer?->taxId ?? null) : null,
            ];
        }

        return [];
    }

    /**
     * Cada modelo expone su colección de determinaciones bajo un nombre
     * distinto; las normalizamos acá para alimentar el DeterminationResource.
     */
    private function getDeterminationsRelation(): Collection
    {
        return match (true) {
            $this->resource instanceof Admission => $this->resource->admissionTests,
            $this->resource instanceof Sample => $this->resource->determinations,
            $this->resource instanceof VetAdmission => $this->resource->vetTests,
            default => collect(),
        };
    }
}
