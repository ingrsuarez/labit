# HOTFIX — Migraciones: pivot FC–remitos + columnas `lab_branch_id`

**TIPO:** Hotfix (despliegue / entornos locales)  
**SLUG:** migraciones-pivot-fc-remitos-y-lab-branch  
**CONTEXTO:** Rama `feature/v1.38.0-stock-por-sede-compras-y-vistas` (o equivalente con mismas migraciones).

---

## Síntomas

1. `php artisan migrate` falla en  
   `2026_04_05_160000_create_delivery_note_purchase_invoice_table`:  
   **`Table 'delivery_note_purchase_invoice' already exists`** (SQLSTATE 42S01).

2. Al crear remito: **`Unknown column 'lab_branch_id' in 'field list'`** en `delivery_notes` — el código ya usa
   `lab_branch_id`, pero la migración que agrega la columna (**`2026_04_05_220000_supply_lab_branch_stock_and_lab_branch_columns`**) **no llegó a ejecutarse** porque la cadena de `migrate` se cortó antes.

---

## Causa raíz

- La tabla pivote se creó en un intento previo (otra rama, migración manual o migrate interrumpido) **sin**
  registrar la fila en `migrations`, **o** se hizo `rollback` parcial.
- Las migraciones son **ordenadas por nombre**: `160000` corre **antes** que `220000`. Si `160000` falla, **nunca**
  se aplican `lab_branch_id` ni `supply_lab_branch_stock`.

---

## Objetivo del hotfix

1. Hacer la migración del pivot **idempotente** en entornos donde la tabla ya existe.
2. Garantizar que **`2026_04_05_220000`** pueda ejecutarse en orden normal tras el arreglo.
3. Documentar **recuperación manual** para quien prefiera no tocar código (solo BD).

---

## PASO A — Cambio en código (recomendado, permanente)

**Archivo:** `database/migrations/2026_04_05_160000_create_delivery_note_purchase_invoice_table.php`

- Antes de `Schema::create`, usar `Schema::hasTable('delivery_note_purchase_invoice')`.
- Si la tabla **ya existe**:
  - **No** llamar a `Schema::create`.
  - Verificar que existan los índices/uniques esperados (opcional, solo si el equipo quiere endurecer).
- El **backfill** desde `purchase_invoices.delivery_note_id`:
  - Debe ser **seguro ante re-ejecución**: insertar solo pares `(purchase_invoice_id, delivery_note_id)` que **no**
    existan ya en la pivote (usar `insertOrIgnore`, o `whereNotExists`, o comprobar antes por `delivery_note_id` único).

**`down()`:** puede seguir con `Schema::dropIfExists` (comportamiento actual).

**Verificación:** en BD vacía (test) la migración sigue creando la tabla; en BD con tabla previa, `migrate` pasa y
registra el batch.

---

## PASO B — Recuperación en local (sin esperar deploy)

Opción para desarrolladores que quieren dejar la BD consistente **ya**:

1. Si la tabla `delivery_note_purchase_invoice` existe y la estructura coincide con la migración:
   - Insertar manualmente en `migrations` el nombre del archivo batch  
     `2026_04_05_160000_create_delivery_note_purchase_invoice_table` **solo si** no está (evitar duplicado).
2. Ejecutar de nuevo: `php artisan migrate`.

**O** tras publicar el PASO A: `php artisan migrate` sin insertar a mano.

> En **producción** no insertar en `migrations` a mano salvo procedimiento acordado; preferir migración idempotente
> + deploy.

---

## PASO C — Tras aplicar `220000`

- Confirmar columnas: `delivery_notes.lab_branch_id`, `purchase_orders.lab_branch_id`,
  `purchase_invoices.lab_branch_id`, `stock_movements.lab_branch_id`, tabla `supply_lab_branch_stock`.
- Crear remito de prueba: no debe aparecer el error de columna inexistente.

---

## PASO D — Commit (sin tag de versión producto, o patch)

```bash
git add database/migrations/2026_04_05_160000_create_delivery_note_purchase_invoice_table.php
git commit -m "fix(migrations): pivot FC-remitos idempotente si la tabla ya existe

- Evita 1050 cuando la BD quedó desincronizada con migrations
- Backfill sin duplicar filas en delivery_note_purchase_invoice
- Desbloquea migración 220000 (lab_branch_id en delivery_notes y stock por sede)"
```

---

## Verificación final

- [ ] `php artisan migrate` completo sin error en copia local con tabla pivote preexistente.
- [ ] `php artisan migrate` en instalación limpia (o `migrate:fresh` en CI) crea pivot + columnas.
- [ ] `delivery-notes` store funciona (columna `lab_branch_id` presente).
- [ ] No se duplican filas en pivote al re-ejecutar lógica de backfill.

---

## Notas

- Si existiera **otra** migración duplicada que cree el mismo nombre de tabla, eliminarla o fusionar (revisar historial git).
- Contabilidad / FC no es objeto de este hotfix; solo esquema y desbloqueo de cadena migratoria.
