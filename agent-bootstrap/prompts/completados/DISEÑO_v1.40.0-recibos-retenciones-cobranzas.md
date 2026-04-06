# Diseño UI — v1.40.0 Retenciones sufridas en recibos de cobro

**Versión:** v1.40.0  
**Fecha diseño:** 2026-04-06  
**Modo:** Mejora de flujo existente (create / edit / show del RC)  
**Layout:** `<x-admin-layout>` + cards `bg-white rounded-xl shadow-sm border border-gray-200` (patrón actual Ventas)

---

## Propósito

Permitir registrar, junto con los medios líquidos, las **retenciones** que practica el cliente al pagar (Ganancias, IVA, SUSS 931, IIBB), manteniendo **claridad contable** y **cuadratura visible** entre lo imputado a facturas y lo que “entra” (efectivo + banco + e-cheq + certificados de retención).

**Usuario:** personal admin con permisos de recibos de cobro (mismo perfil que hoy).

**Acción principal:** guardar un recibo cuyo total cuadre con **medios + retenciones**.

---

## Análisis de la pantalla actual

### Qué funciona bien (mantener)

- Flujo en **tres bloques** claros: Datos generales → Facturas pendientes → Valores recibidos.
- Tabla de facturas con checkbox, saldo en ámbar, total a cobrar en pie de tabla.
- Líneas de pago repetibles con card `border rounded-lg p-4 bg-gray-50/50` y botón **+ Agregar medio**.
- Barra de validación: *Total medios* vs *Total facturas* + mensaje rojo si no coinciden.
- Botón principal deshabilitado hasta cuadrar (Alpine).

### Qué hay que extender

| Aspecto | Estado actual | Necesidad v1.40.0 |
|--------|----------------|-------------------|
| Equación de cierre | `sum(medios) = total facturas` | `sum(medios) + sum(retenciones) = total facturas` |
| Copy “Valores recibidos” | Solo medios de pago | Debe explicitar que hay **dos fuentes** que suman al total |
| Datos de retención | No existen | Tabla tipo “Retenciones realizadas” (referencia usuario) |
| Validación visual | Dos cifras en footer | Tres subtotales + total verificado o barra unificada |
| Show / PDF (si aplica) | Solo pagos | Listado de retenciones legible para auditoría |

### Inconsistencias a evitar

- No usar otro color primario distinto de **zinc/gris** ya usado en esta pantalla (coherencia con `focus:border-zinc-500`, botón `bg-zinc-700`).
- No mezclar retenciones dentro del `<select>` de “Tipo” de medios de pago: son **conceptos distintos** (liquidez vs crédito fiscal / activo retenciones).

---

## Layout general (orden de secciones)

Orden vertical **sin cambiar** el bloque 1 ni 2:

1. **Datos generales** (sin cambios estructurales).
2. **Facturas pendientes** (sin cambios estructurales; el “Total a Cobrar” sigue siendo la verdad del recibo).
3. **Valores recibidos** (medios de pago) — renombrar título o subtítulo para abarcar solo liquidez.
4. **Retenciones sufridas** (nuevo bloque, misma card que el resto).

**Pie del formulario:** barra de totales **única** que cierre la ecuación (ver más abajo).

**Acciones:** *Guardar Recibo de Cobro* y *Volver* como hoy.

---

## Sección nueva: Retenciones sufridas

### Título y ayuda

- **Título:** `Retenciones sufridas` (o `Retenciones practicadas por el cliente`).
- **Texto de ayuda (1 línea):**  
  *Importes retenidos por el cliente según certificado. Se suman al total del recibo junto con los medios de pago.*

### Estructura

- Misma jerarquía visual que “Valores recibidos”:
  - Fila superior: título a la izquierda, botón **`+ Agregar retención`** a la derecha (`inline-flex`, `bg-gray-100`, igual que “+ Agregar medio”).
- Cada fila = **card** `border border-gray-200 rounded-lg p-4 bg-gray-50/50` (paridad con líneas de pago).

### Campos por fila (orden sugerido en desktop)

Grid responsive `grid-cols-1 md:grid-cols-2 lg:grid-cols-3` con última fila o columnas para importe y eliminar.

| Campo | Tipo | Label | Notas UX |
|-------|------|-------|----------|
| Tipo | `<select>` | Tipo * | Opciones: Ganancias, IVA, SUSS (Ley 19.640 / 931), IIBB. Valores internos estables (`ganancias`, `iva`, `suss_931`, `iibb`). |
| Nº documento | text | Nº doc. | Opcional o requerido según reglas de negocio del prompt técnico; en UI mostrar placeholder ej. “8669249”. |
| Régimen | text | Régimen de retención * | Una línea; ej. “Locación de obras y servicios”. |
| Jurisdicción | text | Jurisdicción | Visible siempre; **destacar** (hint `text-amber-700` o ícono `bi-info-circle`) cuando tipo = IIBB: *Requerida para IIBB*. |
| Nº certificado | text | Nº certificado * | |
| Monto retenido | number, step 0.01, alineado derecha | Monto retenido * | Formato monetario con `formatMoney` en lectura; input numérico como medios. |
| Quitar | botón texto rojo | — | Visible si `withholdingLines.length > 0`; permitir quitar todas las líneas (estado “sin retenciones”). |

### Comportamiento

- **Primera carga:** sin filas de retención (suma retenciones = 0) — mismo comportamiento que hoy para usuarios que no retienen.
- **Agregar retención:** duplica card vacía con defaults seguros.
- **Accesibilidad:** labels explícitos; errores de validación bajo el campo (`text-red-500 text-sm`) como en DESIGN_SYSTEM.

### Empty state

- Si no hay líneas: opcional mostrar texto tenue *“No hay retenciones cargadas. Usá + Agregar retención si el cliente te practicó retenciones.”* dentro de la card (no ocupar media pantalla).

---

## Barra de totales y validación (crítico)

Reemplazar el footer actual de solo “Total medios | Total facturas” por una **línea de resumen** más explícita:

**Propuesta A (recomendada — más clara para contadores):**

```
Total medios: $ X  |  Total retenciones: $ Y  |  Suma (medios + retenciones): $ Z
Total imputado a facturas: $ T
```

- Si `|Z - T| ≤ tolerancia` → todo en **gray-800** / check sutil opcional (`bi-check-circle text-green-600`).
- Si no cuadra → misma lógica que hoy: mensaje **rojo** debajo:  
  *La suma de medios más retenciones debe igualar el total a cobrar.*

**Submit:** deshabilitar si `selectedInvoices.length === 0` o si no cuadra la ecuación ampliada.

### Microcopy

- Actualizar la frase bajo “Valores recibidos”:  
  *La suma de los medios de pago **más las retenciones** debe igualar el total a cobrar.*

---

## Pantalla Editar (borrador)

- Paridad total con create: mismas secciones, mismos campos, **precarga** de líneas desde servidor (`initialWithholdingLines` en JSON, análogo a `initialPaymentLines`).
- Misma barra de totales y mismas reglas Alpine.

---

## Pantalla Show (recibo confirmado o borrador)

- Nueva subsección dentro de la card de detalle del RC (ubicación: **después** del listado de medios de pago, **antes** de notas o asiento contable si existe).
- **Título:** `Retenciones`.
- Tabla de solo lectura con columnas alineadas al formulario (Tipo, Nº doc., Régimen, Jurisdicción, Nº certificado, Monto).
- Pie de subtabla: **Total retenciones** en negrita.
- Si no hay retenciones: *Sin retenciones registradas* en texto `text-gray-400 text-sm` (no ocultar la sección si preferís consistencia; u ocultar bloque completo si `count === 0` — **recomendación:** ocultar bloque si vacío para no ruido visual).

---

## Reporte / IVA (si hay UI en v1.40.0)

Si el desarrollo incluye pantalla de **resumen de retenciones IVA** por mes:

- Reutilizar patrón **tabla + filtros** del DESIGN_SYSTEM (página con tabla + filtros).
- Filtros mínimos: mes/año, empresa (si no está fija por contexto).
- Columnas: período, cantidad de comprobantes, total retención IVA.
- Sin gráficos obligatorios en v1.40.0 (opcional Chart.js si sobra tiempo).

---

## Estados de la interfaz

| Estado | Comportamiento |
|--------|----------------|
| Sin cliente | Ocultar facturas y deshabilitar lógica de totales como hoy. |
| Cliente sin facturas pendientes | Igual que hoy. |
| Con facturas, sin medios suficientes pero con retenciones | Permitir cuadrar con retenciones (mensaje de ayuda si Z < T sugiere agregar retención o medio). |
| Error servidor validación | Lista en `bg-red-50` existente. |
| Borrador | Edición completa. |
| Confirmado | Solo show; sin edición de retenciones. |

---

## Componentes y clases (DESIGN_SYSTEM)

- Cards, tablas, botones secundarios grises, botón primario zinc, inputs `rounded-lg border-gray-300 focus:border-zinc-500 focus:ring-zinc-500`.
- Íconos Bootstrap Icons para acciones: `bi-plus-lg`, `bi-trash` si se unifica con otras pantallas.
- Alpine: mismo patrón `x-for`, `x-model`, getters `totalPayments`, añadir `totalWithholdings`, `grandReceived = totalPayments + totalWithholdings`, `paymentsMatchTotal` renombrar internamente a algo como `receiptTotalsMatch` (implementación es del dev; el diseño pide **una sola regla mental**).

---

## Qué NO hacer (decisiones de diseño)

- No integrar retenciones como un “medio de pago” más en el mismo repeater (confunde tesorería con créditos fiscales).
- No usar colores distintos por tipo de impuesto en v1 (tabla uniforme); opcional badge gris por tipo en **show** si mejora escaneo.
- No exigir scroll horizontal en desktop estándar; en móvil `overflow-x-auto` en la tabla de retenciones si hace falta.

---

## Entrega para el programador

1. Implementar según este documento y el prompt `v1.40.0-recibos-retenciones-cobranzas.md`.
2. Mantener **paridad visual** entre línea de pago y línea de retención (misma “densidad” de card).
3. Priorizar **lectura de la ecuación** en el footer antes que adornos.

**Mockup:** no se generó imagen; el layout es continuación del formulario existente y la complejidad es tabular (markdown suficiente según AGENTE_DESIGNER).
