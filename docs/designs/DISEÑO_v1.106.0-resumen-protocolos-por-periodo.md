# Diseño: v1.106.0 — Resumen de protocolos por período (clínico, aguas, veterinario)

> Documento de diseño para **AGENTE_PROGRAMADOR** (contexto obligatorio antes de implementar).
> **Modo:** Mejora sustitutiva del reporte mensual clínico + dos pantallas nuevas (aguas, vet).
> **Diseñado por:** AGENTE_DESIGNER (sesión PM 2026-05-25).
> **Insumos:** Sesión planificación PM, captura reporte actual (filas duplicadas por práctica), `lab/reports/monthly`, `billing/uninvoiced`, `DESIGN_SYSTEM.md`.

---

## Propósito

Ofrecer un **resumen por protocolo** (una fila = un protocolo) para control y rendición a **obra social** (clínico) o **cliente** (aguas/vet), con códigos de determinaciones concatenados y precio total por fila. Reemplaza el formato actual del reporte mensual clínico (una fila por práctica).

**Usuarios:** Administración, facturación, supervisión de lab (`lab-reports.index` clínico; `sales-invoices.index` aguas/vet).

**Acción principal:** Filtrar período + contraparte → revisar tabla → exportar **PDF** o **Excel**.

---

## Reglas de negocio con impacto en UI

| Regla | Implicación |
|--------|-------------|
| **Una fila por protocolo** | No repetir paciente/muestra por cada práctica. |
| **Códigos en columna Determinaciones** | Unir con `-` (ej. `660101-660205`). Sin columna “Práctica” separada en pantalla. |
| **Jerarquía de códigos** | Incluir: determinaciones sueltas, padres/títulos. Excluir hijos si el padre está en el mismo protocolo. Incluir hijo **huérfano** (padre no cargado). |
| **Precio de fila** | Suma de los montos de las prácticas que entraron en la cadena de códigos. Clínico: usar monto OS (`price - copago`) solo en prácticas con `paid_by_patient = false` y `authorization_status != rejected`; omitir prácticas de paciente/rechazadas del string y del total. |
| **Protocolos del período** | Todos (facturados y sin facturar); sin filtro de estado de facturación. |
| **Reemplazo reporte mensual** | Misma ruta/card clínico `lab.reports.monthly`; eliminar tabla “una fila por práctica”. |
| **Tres módulos separados** | Card y vista propia en cada hub (`lab/clinico`, `lab/muestras`, `lab/veterinario`). |

---

## Paleta por módulo

| Módulo | Acento botones primarios / focus |
|--------|----------------------------------|
| Clínico | `teal-600` / `teal-700` (existente lab) |
| Aguas | `cyan-600` / `cyan-700` (muestras) |
| Veterinario | `amber-600` / `amber-700` (vet) |

Patrones compartidos: cards `rounded-xl shadow-sm border`, tablas `min-w-full divide-y`, filtros en card blanca `p-4` o `p-6`.

---

## 1. Laboratorio clínico — Reemplazo de `lab/reports/monthly`

### Navegación

- **Card** en `LabSectionController::clinico()`: mantener nombre **“Reportes”** o renombrar a **“Resumen por período”** con descripción: *“Un protocolo por fila — códigos y totales”*.
- **Ruta:** conservar `lab.reports.monthly` (menos fricción para usuarios que ya la usan).
- **Breadcrumb / título:** `Reporte por Obra Social` o `Resumen por Obra Social`.

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│ Título + subtítulo                                          │
├─────────────────────────────────────────────────────────────┤
│ [Filtros: OS * | Desde | Hasta | Generar] [Limpiar]         │
├─────────────────────────────────────────────────────────────┤
│ (si hay datos)                                              │
│  ┌─ KPIs: Protocolos | Total $ ─────────────────────────┐  │
│  └───────────────────────────────────────────────────────┘  │
│  [Exportar PDF]  [Exportar Excel]     (alineados a la der.) │
│  ┌─ Tabla ───────────────────────────────────────────────┐  │
│  │ OS - Período (header de card)                         │  │
│  │ Fecha | Paciente | DNI | Afiliado | Det. | Precio    │  │
│  └───────────────────────────────────────────────────────┘  │
│  Pie tabla: TOTAL | cantidad protocolos | $ total         │
└─────────────────────────────────────────────────────────────┘
```

### Filtros

| Campo | Tipo | Detalle |
|-------|------|---------|
| Obra social * | `select` | Mismo listado que hoy (`Insurance`, sin nomencladores). `displayName()`. |
| Desde | `input[type=date]` | Reemplaza selector solo mes/año; default: primer día del mes actual. |
| Hasta | `input[type=date]` | Default: hoy. |
| Generar | submit GET | Primario `bg-teal-600`. |
| Limpiar | link | Vuelve a ruta sin query. |

**Atajo opcional (nice-to-have, no bloqueante):** chips “Mes actual” / “Mes anterior” que rellenan desde/hasta.

### KPIs (solo con datos)

Dos cards (no cinco como el reporte viejo):

| KPI | Valor |
|-----|--------|
| Protocolos | `count` filas |
| Total período | `$` suma columna Precio |

Quitar KPIs de “Prácticas”, “Copagos” y “Pacientes únicos” del diseño anterior salvo que negocio los pida después.

### Tabla

| Columna | Contenido | Notas |
|---------|-----------|--------|
| Fecha | `d/m/Y` | `Admission.date` |
| Paciente | `patient.full_name` | |
| DNI | `patient.patientId` o documento habitual | Mantener como captura actual |
| Afiliado | `affiliate_number` o `N/A` | |
| Determinaciones | Códigos unidos `-` | `font-mono text-xs`, `max-w-md truncate` + `title` con string completo |
| Precio | `$` alineado derecha | `font-medium` |

- Orden: fecha asc, luego `id` asc.
- Hover fila: `hover:bg-gray-50`.
- Sin paginación inicial si el volumen mensual es manejable; si >500 filas, paginar en implementación (Laravel paginate 50).

### Pie de tabla

```
┌──────────────────────────────────────────────────────────┐
│ TOTAL          │  N protocolos  │        $ XX.XXX,XX     │
└──────────────────────────────────────────────────────────┘
```

- Fondo: `bg-teal-50` (coherente con módulo).
- Texto “TOTAL” + cantidad de protocolos + suma monetaria en última columna.

### Exportaciones

Botones en fila, `justify-end`, `gap-2`:

| Botón | Estilo | Acción |
|-------|--------|--------|
| Exportar PDF | `bg-gray-700` o `bg-red-600` liviano | GET con mismos query params |
| Exportar Excel | `bg-green-600` | GET, reemplaza `MonthlyInsuranceReportExport` |

**PDF:** landscape A4, cabecera con logo lab, título `{{ OS }} — {{ desde }} al {{ hasta }}`, misma tabla (6 columnas), pie con totales.

**Excel:** mismas columnas; una fila por protocolo; fila final TOTAL.

### Estados

| Estado | UI |
|--------|-----|
| Sin filtros aplicados (sin `insurance_id`) | Empty state central: icono gráfico + “Seleccioná obra social y período…” |
| Filtros OK, 0 protocolos | Empty dentro de card tabla: “No hay protocolos en este período.” |
| Con datos | Tabla + KPIs + exports |

### Qué eliminar respecto al diseño actual

| Antes | Después |
|-------|---------|
| Columnas Código + Práctica por fila | Una columna Determinaciones (solo códigos) |
| Una fila por `admissionTest` | Una fila por `Admission` |
| Solo Excel | PDF + Excel |
| Mes + Año selects | Desde / Hasta (fechas) |
| 5 KPIs | 2 KPIs |

---

## 2. Aguas y alimentos — Nueva vista

### Navegación

- **Card** nueva en `LabSectionController::muestras()`:
  - Nombre: **Resumen por período**
  - Descripción: *“Protocolos por cliente — códigos y totales”*
  - Ruta sugerida: `sample.billing-summary` → `/lab/muestras/resumen` o `/sample/billing-summary`

### Filtros

| Campo | Tipo |
|-------|------|
| Cliente * | `select` (`Customer` activos, aguas) |
| Desde / Hasta | `date` |

### Tabla

| Columna | Contenido |
|---------|-----------|
| Fecha | `entry_date` |
| Muestra | `location` (fallback `product_name`, luego `protocol_number`) |
| Determinaciones | códigos `-` |
| Precio | Σ determinaciones incluidas |

Sin DNI/Afiliado.

### KPIs y pie

Igual patrón: **Protocolos** + **Total $**; pie TOTAL.

### Export

PDF + Excel, acento `cyan-600`.

---

## 3. Veterinario — Nueva vista

### Navegación

- **Card** nueva en `LabSectionController::veterinario()`:
  - Nombre: **Resumen por período**
  - Ruta sugerida: `vet.billing-summary`

### Filtros

| Campo | Tipo |
|-------|------|
| Cliente (veterinaria) * | `select` — clientes tipo veterinario |
| Desde / Hasta | `date` |

### Tabla

| Columna | Contenido |
|---------|-----------|
| Fecha | `date` |
| Animal | `animal_name` |
| Determinaciones | códigos `-` (misma regla jerárquica que clínico) |
| Precio | `total_price` o suma `vetTests` filtrados |

### KPIs, pie, export

Mismo patrón; acento `amber-600`.

---

## Componentes y referencias Blade

- Layout: `x-lab-layout` en las tres.
- Filtros: copiar estructura de `billing/uninvoiced` (flex wrap `gap-3 items-end`).
- Tabla: densidad `text-sm`, headers `uppercase text-xs text-gray-500`.
- Empty states: patrón `lab/reports/monthly` líneas 165–173.
- Permisos: `@can` en cards (`lab-reports.index` / `sales-invoices.index`).

---

## Accesibilidad y responsive

- Tabla con `overflow-x-auto` en mobile.
- Columna Determinaciones: truncar con tooltip nativo `title="{{ $codes }}"`.
- Botones export: icono + texto; en `sm` mostrar texto completo.

---

## Checklist para el programador

- [ ] `BillingSummaryCodeResolver` (o servicio equivalente) con tests de padre/hijo/huérfano
- [ ] Refactor `LabReportController` + vista + `MonthlyInsuranceReportExport` → una fila por protocolo
- [ ] Nuevos métodos/rutas aguas y vet + exports PDF/Excel
- [ ] Cards en `LabSectionController` (3 secciones)
- [ ] Feature tests: totales, concatenación códigos, auth, export 200
- [ ] Eliminar lógica/vista de fila por práctica en clínico

---

## Fuera de scope (diseño)

- Facturar desde el resumen
- Filtro por sede / empresa activa
- Gráficos
- Unificar las tres pantallas en una sola con tabs
