# DISEÑO v1.55.0 — Buscador unificado insumos + servicios en factura de compra

**Versión:** v1.55.0
**Pantalla:** Factura de Compra — Create y Edit (`resources/views/purchase-invoices/create.blade.php`, `edit.blade.php`)
**Modo:** Mejora de pantalla existente
**Fecha:** 2026-04-26
**Diseñado por:** AGENTE_DESIGNER (invocado por AGENTE_PM)

---

## Propósito

Unificar los dos campos separados "Servicio" (select dropdown) y "Descripción / Insumo" (input con búsqueda)
en un único campo de búsqueda que maneje insumos, servicios y texto libre. Mejora la densidad visual
de la tabla y simplifica el flujo de carga.

---

## Análisis de la pantalla actual

### ✅ Qué funciona bien (no cambiar)
- Badge de insumo seleccionado (código teal mono + nombre + ×): limpio, claro
- Dropdown de resultados con navegación de teclado (Tab, Enter, flechas)
- Campos lote/vencimiento condicionales al track_lot del insumo
- `x-show="!item.supply_id"` para ocultar el input al seleccionar: ya implementado

### ⚠️ Problemas a resolver
| Elemento | Problema |
|---|---|
| Columna "Servicio" (`w-52` fija) | Ocupa espacio permanente aunque la mayoría de los ítems son insumos o texto libre |
| Selección de servicio | Flujo en 2 pasos desconectados: elegir en select → ver texto aparecer en otro campo |
| Post-selección de servicio | No hay diferenciación visual de que el ítem es un servicio (vs insumo o texto libre) |
| Encabezados de tabla | "Servicio" + "Descripción / Insumo" como columnas separadas es redundante |

---

## Cambios propuestos

### Tabla de delta

| Elemento | Estado actual | Propuesta |
|---|---|---|
| Columna "Servicio" | `<select>` en `<td>` propio, `w-52` | **Eliminada** |
| Columna "Descripción / Insumo" | Input + búsqueda de insumos | Pasa a ser **"Descripción / Insumo / Servicio"**, más ancha |
| Dropdown de resultados | Solo insumos (API) | **Dos secciones**: INSUMOS (API) + SERVICIOS (client-side) |
| Badge post-selección insumo | Teal (código mono + nombre + ×) | Sin cambios |
| Badge post-selección servicio | No existe (solo actualiza el input de texto) | **Nuevo**: indigo/violet (código + nombre + ×) |
| Campos lote/vencimiento | Solo aparecen si `supply.tracks_lot` | Sin cambios; servicios nunca muestran lote/vencimiento |
| `colspan` en `<tfoot>` | 7 | **6** (una columna menos) |

---

## Diseño del dropdown unificado

**Referencia visual:** `assets/design-v1.55.0-dropdown-unificado.png`

### Estructura del dropdown

```
┌────────────────────────────────────────────────────┐
│  [input] Buscar insumo o servicio...               │
├────────────────────────────────────────────────────┤
│  INSUMOS                                           │  ← sección header: text-xs uppercase text-gray-400, px-3 py-1
│  [INS-00123]  Tiras reactivas H11-800  — Dirui     │  ← code teal mono xs + name sm + brand gray-400 xs
│  [INS-00456]  Tubos EDTA 3ml                       │
├────────────────────────────────────────────────────┤
│  SERVICIOS                                         │  ← sección header igual
│  [SRV]  Alquiler de Equipos  Mantenimiento         │  ← indigo label + name sm + category gray-400 xs
│  [SRV]  Servicio Técnico  Mantenimiento            │
├────────────────────────────────────────────────────┤
│  + Crear insumo "texto búsqueda"                   │  ← solo si no hay resultados de insumos / igual que hoy
└────────────────────────────────────────────────────┘
```

### Reglas de sección

- **INSUMOS**: resultados del endpoint `supplies.search?q=...` (servidor). Si no hay resultados, no mostrar la sección.
- **SERVICIOS**: filtrado client-side de `purchaseServiceGroups`, buscando en `name` y `code` del servicio y en `name` del grupo/categoría. Si no hay resultados, no mostrar la sección.
- Si ninguna sección tiene resultados con texto >= 2 chars → mostrar "Sin resultados" + opción crear insumo.
- Si el texto tiene < 2 chars → no mostrar dropdown.
- Mostrar máximo **6 insumos** y **6 servicios** para no desbordar el viewport.
- Las dos secciones tienen headers separadores: `text-xs font-semibold uppercase tracking-wider text-gray-400 px-3 py-1.5 bg-gray-50`.

### Estilos de fila en dropdown

**Insumo:**
```
px-3 py-2 flex items-center gap-2 hover:bg-teal-50
  [código]  → font-mono text-xs text-teal-700
  [nombre]  → text-sm text-gray-800
  [marca]   → text-xs text-gray-400 "— marca"
```

**Servicio:**
```
px-3 py-2 flex items-center gap-2 hover:bg-indigo-50
  [SRV]       → text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded px-1
  [nombre]    → text-sm text-gray-800
  [categoría] → text-xs text-gray-400
```

---

## Diseño de badges post-selección

**Referencia visual:** `assets/design-v1.55.0-badges-estados.png`

### Badge de insumo seleccionado (sin cambios respecto al actual)

```
inline-flex items-center gap-2
  [INS-00123]  → text-xs font-mono text-teal-700
  [nombre]     → text-xs text-gray-600
  [×]          → text-gray-400 hover:text-red-500, llama unlinkItem()
```

Si el insumo `tracks_lot`: aparecen a la derecha los inputs Lote + Vencimiento (comportamiento actual, sin cambios).

### Badge de servicio seleccionado (nuevo)

```
inline-flex items-center gap-2 px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200
  [código o "SRV"]  → text-xs font-mono text-indigo-700
  [nombre]          → text-xs text-indigo-800
  [×]               → text-indigo-300 hover:text-red-500, llama unlinkItem()
```

**Los campos Lote y Vencimiento NO aparecen** cuando el ítem es un servicio (servicios no tienen stock ni lote).

### Estado: texto libre (sin insumo ni servicio seleccionado)

El input de búsqueda sigue visible. El usuario puede escribir descripción libre.
No aparece badge. No aparecen campos de lote/vencimiento.

---

## Encabezados de tabla modificados

| Columna | Estado actual | Después |
|---|---|---|
| Servicio | `<th>` separado | **Eliminado** |
| Descripción / Insumo | Col separada, ancho flexible | Se fusiona con Servicio → **"Descripción / Insumo / Servicio"**, ocupa todo el espacio ganado |
| Cantidad | Sin cambios | Sin cambios |
| Precio Unit. | Sin cambios | Sin cambios |
| Tasa IVA | Sin cambios | Sin cambios |
| IVA | Sin cambios | Sin cambios |
| Total | Sin cambios | Sin cambios |
| Stock | Sin cambios | Sin cambios |
| (eliminar) | Sin cambios | Sin cambios |

`<tfoot>` rows: cambiar `colspan="7"` a `colspan="6"` en todas las filas de totales.

---

## Flujo de interacción completo

```
1. Usuario hace click en "+ Agregar Ítem"
   → Aparece una nueva fila con el input "Buscar insumo o servicio..." vacío y con foco

2a. Usuario tipea >= 2 caracteres
   → Se dispara búsqueda en API (insumos) en paralelo con filtrado client-side (servicios)
   → Dropdown aparece con secciones INSUMOS y SERVICIOS según resultados disponibles

2b. Usuario selecciona un INSUMO del dropdown
   → Input desaparece
   → Badge teal aparece: [INS-XXXXX] [nombre — marca] [×]
   → Si tracks_lot: campos Lote + Vencimiento aparecen a la derecha
   → Foco se mueve a campo Cantidad

2c. Usuario selecciona un SERVICIO del dropdown
   → Input desaparece
   → Badge indigo aparece: [código SRV] [nombre servicio] [×]
   → No aparecen campos Lote/Vencimiento
   → Foco se mueve a campo Cantidad

2d. Usuario escribe y NO selecciona del dropdown (Tab o Enter sin resaltado)
   → Texto libre queda en el campo description
   → No aparece badge
   → Input sigue visible

3. Usuario hace click en × del badge (insumo o servicio)
   → Badge desaparece
   → supply_id / purchase_service_id se limpian
   → Input vuelve vacío con foco
   → Lote/Vencimiento desaparecen si estaban visibles

4. Usuario guarda la factura
   → supply_id (o vacío) y purchase_service_id (o vacío) se envían como hidden inputs
   → Backend sin cambios
```

---

## Estados de la celda "Descripción / Insumo / Servicio"

| Estado | Qué mostrar |
|---|---|
| Vacío, sin foco | Input con placeholder "Buscar insumo o servicio..." |
| Tipeo en curso (< 2 chars) | Input con texto, sin dropdown |
| Tipeo en curso (>= 2 chars) | Input + dropdown con secciones |
| Insumo seleccionado | Badge teal + (lote/vto si tracks_lot) |
| Servicio seleccionado | Badge indigo |
| Texto libre guardado | Input con texto, sin badge |
| Buscando (loading) | Input con texto, spinner en dropdown o sin dropdown |

---

## Componentes Alpine a modificar

- **`supplySearch(item, index)`** → renombrar a **`itemSearch(item, index, serviceGroups)`**
  - Recibe `serviceGroups` como tercer parámetro (pasado desde el `x-data` del `<td>`)
  - Agrega filtrado client-side de servicios por texto
  - Agrega `selectService(svc)` function
  - Modifica `unlinkSupply()` → `unlinkItem()` (limpia también `purchase_service_id`)
  - El badge cambia: `x-show="item.supply_id || item.purchase_service_id"`
  - Badge renderizado condicionalmente: teal si `supply_id`, indigo si `purchase_service_id`

---

## Lo que NO cambia

- La lógica del campo `description` (texto libre): sigue funcionando igual
- El modal de "Crear insumo nuevo": sin cambios
- El envío de hidden inputs: `supply_id`, `purchase_service_id`, `description` ya existen, sin cambios
- El backend (`PurchaseInvoiceController`): sin cambios
- La vista `show.blade.php`: sin cambios
- El PDF de factura de compra: sin cambios

---

## Notas para el programador

1. `purchaseServiceGroups` ya está disponible en `invoiceForm()` como `this.purchaseServiceGroups`. Pasarlo al `itemSearch` como:
   ```html
   <td x-data="itemSearch(item, index, purchaseServiceGroups)">
   ```
2. El filtrado client-side de servicios busca en `service.name`, `service.code`, y `group.name` (categoría).
3. Al seleccionar un servicio, limpiar `item.supply_id`, `item.lot_number`, `item.expiration_date`, `item.updates_stock = false` — igual que hacía `selectPurchaseService()` antes.
4. El método `selectPurchaseService()` en `invoiceForm()` puede eliminarse (ya no hay select de servicios).
5. La imagen del mockup del dropdown está en `assets/design-v1.55.0-dropdown-unificado.png`.
6. La imagen de badges post-selección está en `assets/design-v1.55.0-badges-estados.png`.
7. Los cambios van en `create.blade.php` **y** `edit.blade.php` — las estructuras son casi idénticas.
