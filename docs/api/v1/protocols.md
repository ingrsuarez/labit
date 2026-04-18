# API v1 — Protocolos

> Endpoints públicos para integraciones externas (LISCOM HL7 u otros equipos
> que necesiten consultar/sincronizar protocolos del laboratorio).
> Versión: **v1.47.0** — Liberado: 2026-04-18.

## Autenticación

Todos los endpoints requieren un header `X-API-Key` válido. Ver
[`v1.46.0 — API Pública: Fundación`](../../../agent-bootstrap/prompts/completados/v1.46.0-api-publica-fundacion.md)
para la creación y administración de keys.

```
X-API-Key: labit_<40 caracteres>
```

Cada key está atada a una **sede** (`lab_branch_id`); todos los endpoints
filtran automáticamente por esa sede. Una key NO puede ver protocolos de otra
sede aunque pertenezca a la misma empresa.

### Niveles de exposición de PII

La key tiene un campo `patient_data_level` que decide si la API expone el DNI
del paciente (`patient.document`):

| Nivel       | Expone DNI | Cuándo usarlo                                   |
|-------------|-----------|--------------------------------------------------|
| `minimal`   | No        | Default. Recomendado para integraciones internas. |
| `standard`  | Sí        | Solo si la integración necesita validar paciente contra el sistema externo. Requiere justificación legal/operativa. |

---

## Endpoints

### `GET /api/v1/protocols`

Listado unificado de los 3 tipos de protocolo de la sede de la key.

#### Query params

| Param              | Default                  | Descripción |
|--------------------|--------------------------|-------------|
| `date_from`        | hoy                      | Fecha desde (YYYY-MM-DD). Para `sample` aplica sobre `entry_date`. |
| `date_to`          | hoy                      | Fecha hasta (YYYY-MM-DD). Para `sample` aplica sobre `entry_date`. |
| `status`           | `pending,in_progress`    | CSV de estados. Usar `*` para todos. |
| `type`             | (todos)                  | CSV: `clinical,sample,vet`. |
| `updated_since`    | —                        | ISO 8601. Para sync incremental. |
| `protocol_number`  | —                        | Match exacto. |
| `per_page`         | 100                      | Entre 1 y 500. |
| `page`             | 1                        | Paginación manual. |

#### Response

```json
{
  "data": [ /* array de Protocol */ ],
  "meta": {
    "current_page": 1,
    "per_page": 100,
    "total": 42,
    "last_page": 1
  }
}
```

---

### `GET /api/v1/protocols/by-barcode/{code}`

Lookup directo por `protocol_number` exacto (lo que el equipo HL7 acaba de
escanear). El primer carácter del code (`C`/`A`/`V`) selecciona el modelo
inicialmente; si no matchea, hace fallback en los 3.

- **200**: estructura `{ "data": Protocol }`.
- **404**: no encontrado en la sede de la key (incluye protocolos de otra sede).

---

### `GET /api/v1/protocols/{type}/{id}`

Detalle por `type` (`clinical|sample|vet`) e `id` interno. La URL aparece como
`links.self` en el listado y permite acceso idempotente.

- **200**: estructura `{ "data": Protocol }`.
- **404**: type inválido o id no pertenece a la sede de la key.

---

## Estructura unificada `Protocol`

```jsonc
{
  "id": 123,
  "type": "clinical",                 // clinical | sample | vet
  "protocol_number": "C2604180012",
  "barcode": "C2604180012",           // == protocol_number en v1.47.0
                                      //  (formato extendido `^material` en v1.48.5)
  "date": "2026-04-18",
  "status": "pending",
  "lab_branch": {
    "id": 1,
    "name": "Sede Centro"
  },
  "patient": {                        // ver "Polimorfismo del paciente" abajo
    "id": 45,
    "display_name": "Pérez, Juan",
    "sex": "M",
    "age_years": 46,
    "species": null,                  // solo en vet
    "breed": null,                    // solo en vet
    "animal_name": null,              // solo en vet
    "document": null                  // null si patient_data_level=minimal
  },
  "determinations": [
    {
      "id": 999,
      "test_id": 17,
      "test_code": null,              // external_code se completa en v1.49.0
      "test_name": "Glucemia",
      "material": {
        "id": 3,
        "name": "Suero",
        "abbreviation": "SUE"
      },
      "unit": "mg/dl",
      "reference_value": "70-100",
      "status": "pending",            // pending|in_progress|completed|validated
      "has_result": false
    }
  ],
  "updated_at": "2026-04-18T18:33:21+00:00",
  "links": {
    "self": "/api/v1/protocols/clinical/123"
  }
}
```

### Polimorfismo del paciente

| Tipo       | `display_name`                        | Campos extra                                                |
|------------|---------------------------------------|-------------------------------------------------------------|
| `clinical` | `"Apellido, Nombre"` del paciente     | `sex`, `age_years`, `document` (gated por PII level).       |
| `sample`   | Nombre del **cliente** (empresa)      | `location`, `batch`, `product_name`. Sin paciente humano.   |
| `vet`      | `"Dueño / Animal"`                    | `species`, `breed`, `animal_name`, `age_years`. `document` gated por PII level (CUIT del dueño). |

### Estados de determinación

Los modelos internos usan campos heterogéneos (`authorization_status` +
`is_validated` + `result` para clínicas, `status` enum para muestras y vet).
La API los normaliza a:

- `pending` — sin autorizar / sin trabajar.
- `in_progress` — autorizado y en proceso, sin resultado todavía.
- `completed` — tiene resultado pero falta validación bioquímica.
- `validated` — validado y firmado.

---

## Notas de seguridad y rate limiting

- Todas las queries son `lab_branch_id`-bound. NO existe modo cross-branch.
- El middleware `auth.api_key` ya rechaza keys inactivas (401) y registra
  `requests_count` y `last_used_at` por request.
- Rate limiting global del proyecto aplica (ver config/api). Si una integración
  necesita más throughput, ajustar a nivel infra.
