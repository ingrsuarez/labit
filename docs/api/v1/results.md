# API v1 — Ingesta de resultados (`POST /api/v1/results/batch`)

> Versión: **v1.51.0** | Introducido: 2026-04-18

---

## Descripción

Endpoint batch que recibe los resultados ya validados humanamente por LISCOM y los persiste contra las determinaciones de laboratorio en labit. Cubre los tres tipos de protocolo (clínico, muestras, vet) en un único endpoint.

**Regla crítica:** si una determinación ya fue validada por un bioquímico de labit (`is_validated = true`), el valor entrante **NO se sobrescribe**. El ítem se rechaza con `ALREADY_VALIDATED` y se devuelve la información de auditoría para que LISCOM lo marque como definitivamente bloqueado.

---

## Endpoint

```
POST /api/v1/results/batch
```

### Headers

| Header | Requerido | Descripción |
|---|---|---|
| `X-API-Key` | ✅ | Clave de API activa del cliente (generada en `api_clients`) |
| `Content-Type` | ✅ | `application/json` |

---

## Request body

```json
{
  "batch_id": "550e8400-e29b-41d4-a716-446655440000",
  "items": [
    {
      "external_message_id": "MSG-001",
      "hl7_control_id": "TEST-9485",
      "protocol_number": "C2604180001",
      "equipment_name": "Cobas-411",
      "results": [
        {
          "labit_test_id": 145,
          "value": "95",
          "unit": "mg/dL",
          "reference_range": "70-110",
          "abnormal_flag": "N",
          "obx_index": 1
        }
      ]
    }
  ]
}
```

### Campos del body

| Campo | Tipo | Req. | Descripción |
|---|---|---|---|
| `batch_id` | UUID | ✅ | Identificador único del batch generado por LISCOM. Garantiza idempotencia a nivel batch. |
| `items` | array | ✅ | Lista de mensajes. Máximo 500. |
| `items[].hl7_control_id` | string(100) | ✅ | Control ID del mensaje HL7 (campo MSH-10). Garantiza idempotencia a nivel mensaje. |
| `items[].protocol_number` | string(50) | ✅ | Número de protocolo labit. Prefijo determina el tipo: `C` = clínico, `A` = muestras, `V` = vet. |
| `items[].external_message_id` | string(100) | — | ID externo del mensaje (campo opcional de LISCOM). |
| `items[].equipment_name` | string(100) | — | Nombre del equipo analizador. |
| `items[].results` | array | ✅ | Lista de resultados individuales. Mínimo 1. |
| `items[].results[].labit_test_id` | integer | ✅ | ID del test en labit (`tests.id`). Debe existir en la BD. |
| `items[].results[].value` | string(255) | ✅ | Valor del resultado. |
| `items[].results[].obx_index` | integer | ✅ | Índice del segmento OBX (0-based). Incluido en la response para trazabilidad. |
| `items[].results[].unit` | string(50) | — | Unidad de medida. |
| `items[].results[].reference_range` | string(100) | — | Rango de referencia del equipo. |
| `items[].results[].abnormal_flag` | string(10) | — | Flag de anormalidad (N/A/H/L/etc). |

---

## Response

### HTTP 200 — Procesado (incluso si ítems fueron rechazados)

```json
{
  "batch_id": "550e8400-e29b-41d4-a716-446655440000",
  "items": [
    {
      "external_message_id": "MSG-001",
      "hl7_control_id": "TEST-9485",
      "status": "partial",
      "protocol_number": "C2604180001",
      "results": [
        {
          "obx_index": 1,
          "status": "ingested",
          "determination_id": 42,
          "previous_value": null
        },
        {
          "obx_index": 2,
          "status": "overwritten",
          "determination_id": 43,
          "previous_value": "12.5"
        },
        {
          "obx_index": 3,
          "status": "rejected",
          "reason": "ALREADY_VALIDATED",
          "determination_id": 44,
          "validated_at": "2026-04-18T15:30:00+00:00",
          "validated_by_name": "Dra. Ana García"
        }
      ]
    }
  ]
}
```

### HTTP 200 — Batch duplicado (mismo `batch_id`)

```json
{
  "batch_id": "550e8400-e29b-41d4-a716-446655440000",
  "duplicate": true,
  "items": [ /* ...mismos items del procesamiento original... */ ]
}
```

### HTTP 422 — Validación fallida

```json
{
  "message": "The batch id field must be a valid UUID.",
  "errors": {
    "batch_id": ["The batch id field must be a valid UUID."]
  }
}
```

### HTTP 401 — Sin autenticación o key inválida/inactiva

```json
{
  "error": "API key required",
  "code": "API_KEY_MISSING"
}
```

---

## Status codes por ítem (level: message)

| `status` | Descripción |
|---|---|
| `ingested` | Todos los resultados del mensaje fueron ingresados o sobrescritos exitosamente. |
| `partial` | Al menos uno fue ingresado/sobrescrito y al menos uno fue rechazado. |
| `rejected` | Todos los resultados del mensaje fueron rechazados, o el protocolo no existe. |
| `duplicate` | El `hl7_control_id` ya fue procesado para este `api_client`. Se devuelven los resultados originales. |

## Status codes por resultado (level: OBX)

| `status` | Descripción |
|---|---|
| `ingested` | Valor guardado (la determinación no tenía valor previo). |
| `overwritten` | Valor sobrescrito (había un valor previo no validado). Se devuelve `previous_value`. |
| `rejected` | Valor rechazado. Ver `reason`. |

## Razones de rechazo (`reason`)

| `reason` | Descripción |
|---|---|
| `ALREADY_VALIDATED` | La determinación tiene `is_validated = true`. El bioquímico ya la validó. NO se sobrescribe nunca. Se devuelven `validated_at` y `validated_by_name`. |
| `DETERMINATION_NOT_FOUND` | No existe una determinación para ese `protocol_number` + `labit_test_id`. |
| `PROTOCOL_NOT_FOUND` | No existe un protocolo con ese `protocol_number`. |
| `PROTOCOL_OUT_OF_BRANCH` | El protocolo existe pero pertenece a una sede diferente a la del API client. |

---

## Idempotencia

El endpoint es **doblemente idempotente**:

1. **A nivel batch** (`batch_id`): si el mismo `batch_id` se recibe dos veces del mismo `api_client`, la segunda respuesta devuelve los resultados del primer procesamiento con `"duplicate": true`. La BD no se modifica.

2. **A nivel mensaje** (`hl7_control_id`): si el mismo `hl7_control_id` aparece en un nuevo batch (mismo `api_client`), ese ítem se devuelve con `status: duplicate` y no se procesa nuevamente.

---

## Reglas de negocio

1. **Prefijos de protocolo:** `C` → clínico (`admissions`), `A` → muestras (`samples`), `V` → vet (`vet_admissions`).

2. **No sobrescribir validados:** Si `admission_tests.is_validated = true`, el ítem se rechaza con `ALREADY_VALIDATED`. Esta regla no tiene excepciones. LISCOM debe marcar ese resultado como `rejected_already_validated` y no reintentar.

3. **Sobrescribir no validados:** Si la determinación tiene un valor pero `is_validated = false`, el nuevo valor lo reemplaza. La response incluye `previous_value` y se genera log de auditoría.

4. **Seguridad por sede:** El protocolo debe pertenecer a la misma `lab_branch_id` que el `api_client`. Si no, se rechaza con `PROTOCOL_OUT_OF_BRANCH`.

5. **Raw request limitado:** Si el payload supera 64KB, solo se guarda `{ "_truncated": true, "batch_id": "...", "items_count": N }` en `result_batches.raw_request`.

---

## Logging

Todas las operaciones generan logs en el canal `api` con keys estructuradas:

| Key | Cuándo |
|---|---|
| `api.results.batch.duplicate` | Batch recibido por segunda vez |
| `api.results.batch.processed` | Batch procesado exitosamente |
| `api.results.item.overwritten` | Valor sobrescrito en una determinación |
| `api.results.item.rejected_already_validated` | Ítem rechazado por validación previa |

---

## Modelos de auditoría creados

- **`result_batches`**: cabecera de cada POST (1 fila por request único). Contiene contadores de ítems.
- **`result_ingestions`**: detalle de cada mensaje (1 fila por `hl7_control_id` único). Contiene `items_summary` JSON con el resultado de cada OBX.

---

## Próximas versiones relacionadas

- **v1.52.0** (LISCOM): cola de envío que toma `ResultMessage` aprobados y los postea a este endpoint.
- **v1.53.0** (labit): UI admin de monitoreo de batches (`result_batches` + `result_ingestions`).
