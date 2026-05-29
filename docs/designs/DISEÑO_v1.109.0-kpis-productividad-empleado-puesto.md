# Diseño: v1.109.0 — KPIs diarios de productividad por empleado y puesto

> Documento de diseño para **AGENTE_PROGRAMADOR** (contexto obligatorio antes de implementar).
> **Modo:** Pantalla nueva en RRHH.
> **Diseñado por:** AGENTE_DESIGNER (sesión PM 2026-05-28).
> **Insumos:** Sesión planificación PM, organigrama IPAC (Neuquén / Cipolletti), `audit_logs`, roles `recepcion-lab` / `tecnico-lab` / `bioquimico`, `rrhh/resumen`, `LabDashboardService`, `DESIGN_SYSTEM.md`.

---

## Propósito

Panel de **productividad diaria** por empleado, alineado al **puesto** que ocupa en el organigrama (`Job` ↔ `Employee` ↔ `User`). Permite a administración y supervisión ver qué hizo cada persona en el día (o fecha elegida), filtrado por **sede** y por **tipo de laboratorio** (clínico, vet, aguas).

**Usuarios:** `admin`, `contador` (y roles con permiso `rrhh.productivity.view` si se crea).

**Acción principal:** Elegir fecha + sede → revisar tabla de empleados con KPIs según su rol → exportar CSV.

**Principio de medición:** Todo KPI es **del día calendario** (00:00–23:59, timezone app). Denominador global acordado: **protocolos creados en la sede ese día** (clínico + vet + aguas).

---

## Reglas de negocio con impacto en UI y datos

| Regla | Implicación |
|--------|-------------|
| KPIs diarios | Filtro fecha default = **hoy**; no histórico largo en MVP |
| Denominador | Columna/fila resumen: **Protocolos del día (sede)** |
| Tres módulos | Desglose por tipo en columnas secundarias o tooltip |
| Atribución por usuario | `audit_logs.user_id` o campos `validated_by` / `result_entered_by` |
| Excluir API/LISCOM | Ingestas automáticas no suman en ranking humano |
| **Resultado entregado (recepción)** | **1 por protocolo por empleado por día**, aunque imprima 3 veces o reenvíe mail |
| Entrega válida | Solo protocolos con al menos una determinación **validada** (misma regla que envío mail hoy) |
| Canales de entrega | Email (`email_sent`) **o** impresión/descarga PDF informe (`lab-reports.print` / `samples-reports.print`) — no preview en navegador |

### KPI «Resultados entregados» (recepcionista) — regla técnica

**Definición de negocio:** Cantidad de protocolos distintos cuyo resultado fue **entregado al paciente/cliente** ese día por ese empleado (impreso o enviado por correo).

**No cuenta:**
- Segunda, tercera impresión del mismo protocolo el mismo día por el mismo usuario
- Reenvío de email del mismo protocolo el mismo día por el mismo usuario
- `pdf_generated` con descripción «Visualizó PDF» (preview)
- Protocolos sin validación previa

**Implementación recomendada (idempotente):**

1. Nueva acción de auditoría: `result_delivered`.
2. Al ejecutar **primer** envío mail o **primer** download PDF de informe del día para ese `(user_id, auditable_type, auditable_id)`:
   - Si **no** existe `audit_logs` con `action = result_delivered` y misma terna en el día → `logAudit('result_delivered', 'Entregó resultado del protocolo Nº …')`.
   - Si ya existe → no crear otro log (las acciones `email_sent` / `pdf_generated` pueden seguir registrándose para trazabilidad, pero el KPI usa solo `result_delivered`).
3. KPI recepcionista = `COUNT(*)` de `result_delivered` del usuario en el día (ya es único por protocolo).

**Puntos de enganche (clínico / vet / muestras):**
- `LabAdmissionController::sendEmail`, `downloadPdf` (no `viewPdf`)
- `VetAdmissionController::sendEmail`, `downloadPdf` (no `viewPdf`)
- `SampleController::sendEmail`, `downloadPdf` (no `viewPdf`)
- Envío masivo: **1 `result_delivered` por protocolo** incluido en el lote (mismo criterio idempotente por protocolo)

**Etiqueta en UI:** «Resultados entregados» con tooltip: *Protocolos distintos con informe entregado (mail o impresión), una vez por protocolo por día.*

---

## Catálogo de KPIs por rol (columnas de la tabla)

### Resumen de sede (fila o cards arriba de la tabla)

| KPI | Descripción |
|-----|-------------|
| Protocolos del día | Creados en la sede (C + V + A) |
| Pacientes del día | Distintos (informativo, no denominador) |

### Recepcionista (`recepcion-lab` y puestos tipo Secretaría)

| Columna | Fuente |
|---------|--------|
| Protocolos creados | `audit_logs.created` (distinct auditable) |
| Pacientes nuevos | `Patient` + `created` |
| Protocolos editados | `updated` |
| Cobros particulares | `payment_recorded` (nuevo) |
| **Resultados entregados** | `result_delivered` (único/protocolo/día) |
| Tasa entregados / protocolos del día | ratio |

Desglose opcional en tooltip: creados clínico / vet / aguas.

### Técnico de laboratorio (`tecnico-lab`)

| Columna | Fuente |
|---------|--------|
| Determinaciones con resultado | `result_entered_at` del día |
| Protocolos con carga | distinct admission con ≥1 resultado ingresado |
| Eventos carga (protocolo) | `results_loaded` |
| Tasa carga / protocolos del día | ratio |
| Prácticas eliminadas | `test_removed` |

### Bioquímico (`bioquimico`)

| Columna | Fuente |
|---------|--------|
| Prácticas validadas | `validated_at` + `validated_by` |
| Protocolos validados | distinct protocolo con validación del día por usuario |
| Tasa validadas / protocolos del día | ratio (denominador acordado) |
| Desvalidaciones | `unvalidated` |
| Informes enviados | `email_sent` (bioquímico; distinto de entrega recepción) |
| Resultados cargados | `results_loaded` o `result_entered_*` |

---

## Paleta y layout

| Elemento | Estilo |
|----------|--------|
| Módulo | RRHH — acento **indigo** (`indigo-600` botones, links como `rrhh/resumen`) |
| Cards KPI sede | Mismo patrón que `rrhh/resumen`: `bg-white rounded-xl shadow-sm border p-6` |
| Tabla | `min-w-full divide-y`, scroll horizontal en mobile |

---

## Navegación

- **Ruta:** `GET /rrhh/productividad` → `rrhh.productividad`
- **Entrada hub RRHH:** card en sección **Personal** (junto a Empleados / Puestos) o link en header de **Resumen RRHH** para admin/contador
- **Breadcrumb:** Recursos Humanos → Productividad diaria

---

## Layout de pantalla

```
┌─────────────────────────────────────────────────────────────────┐
│ ← Volver a RRHH                                                 │
│ Productividad diaria                                            │
│ Métricas por empleado según puesto — laboratorio                │
├─────────────────────────────────────────────────────────────────┤
│ [Fecha *] [Sede ▼] [Puesto ▼] [Empleado ▼]  [Aplicar] [Hoy]    │
├─────────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐            │
│ │ Protocolos   │ │ Entregados   │ │ Validados    │  …sede      │
│ │ del día: 84  │ │ (recep): 62  │ │ (bio): 71    │            │
│ └──────────────┘ └──────────────┘ └──────────────┘            │
├─────────────────────────────────────────────────────────────────┤
│ [Exportar CSV]                                                  │
│ ┌─ Tabla ─────────────────────────────────────────────────────┐ │
│ │ Empleado | Puesto | Sede | Rol | KPI-1 | KPI-2 | … | %     │ │
│ │ …                                                           │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Nota: columnas visibles según rol detectado del empleado        │
└─────────────────────────────────────────────────────────────────┘
```

---

## Filtros

| Campo | Tipo | Default | Comportamiento |
|-------|------|---------|----------------|
| Fecha | `input[type=date]` | Hoy | Submit GET al cambiar «Aplicar» |
| Sede | `select` | Todas o sede del usuario si tiene default | `LabBranch` activas |
| Puesto | `select` | Todos | `Job` con empleados |
| Empleado | `select` | Todos | Filtrado por puesto si aplica |
| Aplicar | submit | — | Recarga tabla |
| Hoy | link/button | — | Reset fecha a hoy |

---

## Tabla principal

### Columnas fijas

| Columna | Contenido |
|---------|-----------|
| Empleado | `Employee` nombre + apellido; link a perfil si existe |
| Puesto | `Job.name` principal (si varios, el de lab o el primero) |
| Sede | Inferida de actividad del día (sede con más eventos) o sede default usuario |
| Rol operativo | Badge: Recepción / Técnico / Bioquímico (desde Spatie role) |

### Columnas dinámicas por rol

Mostrar solo columnas del rol del empleado. Si tiene varios roles, mostrar **todas** las columnas que correspondan (caso director-bioquímico).

| Rol | Columnas (orden sugerido) |
|-----|---------------------------|
| Recepcionista | Creados · Pac. nuevos · Editados · Cobros · **Entregados** · % entregados |
| Técnico | Det. cargadas · Prot. con carga · % carga |
| Bioquímico | Val. prácticas · Val. protocolos · % validados · Desvalid. · Emails |

**Formato numérico:** enteros; porcentajes con 1 decimal (`72,5 %`).

**Tooltip en «Entregados»:** icono `bi-info-circle` — texto de regla único por protocolo.

---

## Estados de la pantalla

| Estado | Qué mostrar |
|--------|-------------|
| Sin empleados con actividad | Empty: «No hay actividad registrada para esta fecha y filtros» |
| Con datos | Tabla + cards resumen sede |
| Empleado sin `user_id` | Excluir de tabla o fila gris «Sin usuario sistema» |
| Cargando | Skeleton en cards y 5 filas tabla (opcional; MVP puede ser full page load) |

---

## Export CSV

- Botón secundario `bg-white border` arriba a la derecha de la tabla
- Mismas columnas visibles + fecha/sede en nombre archivo: `productividad-2026-05-28-neuquen.csv`
- Separador `;` o `,` según convención exports existentes del proyecto

---

## Permisos

- Nuevo: `rrhh.productivity.view`
- Asignar a: `admin`, `contador`
- No visible para roles solo lab (salvo que negocio pida después)

---

## Componentes a usar

| Necesidad | Componente |
|-----------|------------|
| Layout | `x-admin-layout` |
| Cards KPI | Patrón `rrhh/resumen` |
| Tabla | `DESIGN_SYSTEM` tabla estándar + `overflow-x-auto` |
| Badges rol | `rounded-full px-2.5 py-0.5 text-xs` — teal recepción, blue técnico, purple bioquímico |
| Filtros | Card blanca `p-4`, grid `md:grid-cols-4` |
| Volver | Link texto `← Volver a Recursos Humanos` como resumen |

---

## Instrumentación requerida (no UI — contexto Dev)

Además de la pantalla, la versión debe incluir:

| Cambio | Motivo |
|--------|--------|
| `result_entered_by` / `result_entered_at` en clínico y vet | KPI técnico justo |
| `logAudit` en `WorksheetController::saveResults` | Planilla sin traza hoy |
| `payment_recorded` | Cobros recepción |
| **`result_delivered` idempotente** | KPI entregados recepción |
| `AuditLog::ACTION_LABELS` + color para nuevas acciones | Vista auditoría legible |

---

## Qué NO diseñar en esta versión

- Gráficos de tendencia / Chart.js
- Metas por puesto configurables
- Vista «Mi productividad» para el empleado
- Comparativa entre sedes lado a lado
- Drill-down a lista de protocolos entregados (→ v1.109.1)

---

## Referencia visual

Patrón más cercano: **`resources/views/rrhh/resumen.blade.php`** (cards + tabla actividad), no el dashboard operativo del lab (ese es por protocolo/sede, no por empleado).

---

## Checklist para el programador

- [ ] Leer este documento antes de `EmployeeProductivityService`
- [ ] Implementar `result_delivered` antes de mostrar columna «Entregados»
- [ ] Validar idempotencia: 3 prints mismo protocolo → KPI +1, no +3
- [ ] Tests Feature: recepcionista entrega, técnico carga, bioquímico valida — mismo día, misma sede
- [ ] Link en `RrhhNavigation` sección Personal
