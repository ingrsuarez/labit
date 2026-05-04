# API v1 — Catálogo de Tests/Determinaciones

> Endpoint para búsqueda en el catálogo de tests de labit.
> Pensado para que LISCOM configure mapeos de códigos por equipo (EquipmentTestMapping).

---

## Autenticación

Igual que el resto de la API v1: header `X-API-Key` con una key válida y activa (v1.46.0).

---

## `GET /api/v1/tests`

Busca tests por nombre o código. Requiere `search` con al menos 2 caracteres.

### Query params

| Param | Tipo | Default | Descripción |
|---|---|---|---|
| `search` | string | — | **Requerido** (min 2 chars). Busca por `name` y `code` con LIKE. |
| `category` | string | — | Filtro opcional: `clinico`, `aguas_alimentos`, `veterinario`. |
| `per_page` | int | 30 | Resultados por página (1–100). |
| `page` | int | 1 | Página actual. |

### Respuesta exitosa (200)

```json
{
  "data": [
    {
      "id": 42,
      "code": "GLU",
      "name": "Glucosa",
      "unit": "mg/dL",
      "method": "Espectrofotometría",
      "nbu": 1.5,
      "categories": ["clinico"],
      "is_parent": false,
      "is_child": true,
      "material": {
        "id": 3,
        "name": "EDTA Tubo",
        "abbreviation": "EDTA"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 30,
    "total": 1,
    "last_page": 1
  }
}
```

### Campos del test

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | int | ID del test en labit (usar como `labit_test_id` en EquipmentTestMapping). |
| `code` | string | Código corto del test (usar como `labit_test_code`). |
| `name` | string | Nombre completo (usar como `labit_test_name`). |
| `unit` | string\|null | Unidad de medida (mg/dL, g/L, etc.). |
| `method` | string\|null | Método analítico. |
| `nbu` | float\|null | Valor NBU del test. |
| `categories` | array | Categorías: `clinico`, `aguas_alimentos`, `veterinario`. |
| `is_parent` | bool | `true` si es un test agrupador (ej: "Hemograma"). No mapear como determinación. |
| `is_child` | bool | `true` si pertenece a un grupo padre. |
| `material` | object\|null | Material requerido (tubo/contenedor). |
| `material.id` | int | ID del material. |
| `material.name` | string | Nombre completo. |
| `material.abbreviation` | string | Sigla corta (ej: EDTA, SUE). |

### Notas de uso para LISCOM

1. **Solo mapear tests con `is_parent: false`** — los padres son headers de grupo, no determinaciones reales.
2. **Usar `id` como `labit_test_id`**, `code` como `labit_test_code` y `name` como `labit_test_name` al crear EquipmentTestMapping.
3. **Filtrar por `category`** para obtener solo los tests del tipo de laboratorio del equipo.
4. **`material.abbreviation`** puede usarse como `material_filter` en EquipmentTestMapping si el equipo solo procesa ciertos materiales.

### Errores

| Código | Descripción |
|---|---|
| 401 | API key faltante, inválida o inactiva. |

---

> Documentación creada con v1.67.0 (2026-05-04).
