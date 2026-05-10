# Diseño UI — v1.86.0 Envío masivo de protocolos clínicos por email

**Versión:** v1.86.0  
**Pantalla:** `resources/views/lab/admissions/index.blade.php` (mejora; layout `x-lab-layout`)  
**Modo Designer:** mejora de pantalla existente  
**Fecha:** 2026-05-10  
**Referencia interna:** mismo patrón operativo que `resources/views/sample/index.blade.php` (FAB + modal + Alpine), adaptado al flujo **un solo destinatario / un solo correo / N adjuntos**.

---

## Propósito

Permitir al personal del laboratorio seleccionar varios protocolos **validados** desde el listado de admisiones y enviar **un único correo** con todos los informes PDF adjuntos, típicamente hacia una **obra social** o un correo manual (gestor, institución, paciente).

**Usuario:** roles con permiso de ver admisiones y enviar informes (alineado a `sendEmail` en detalle).

**Acción principal:** seleccionar filas → abrir modal → confirmar destinatario → enviar.

---

## Patrón de consistencia (no reinventar)

| Elemento | Origen en el proyecto |
|----------|----------------------|
| Botón flotante inferior derecho (`fixed bottom-6 right-6 z-50`) | `sample/index.blade.php` |
| Botón primario **teal** (`bg-teal-600`, hover `700`) | Listado admisiones actual |
| Modal centrado overlay `bg-black bg-opacity-40`, panel `rounded-2xl shadow-2xl max-w-2xl` | `sample/index.blade.php` |
| Checkbox tabla + `selectAll` en cabecera | `sample/index.blade.php` |
| Icono sobre sobre botón flotante | SVG correo ya usado en muestras |

El desarrollo debe **reutilizar tokens visuales** ya presentes en admisiones (rounded-xl, border-gray-200, focus ring teal) para que la nueva UI no parezca otro producto.

---

## Cambios propuestos respecto al listado actual

| Elemento | Estado actual | Propuesta |
|----------|---------------|-----------|
| Primera columna de tabla | No existe | Columna estrecha (~4%) con checkbox “seleccionar fila” + en `<thead>` checkbox “seleccionar página” |
| Filas elegibles | — | Checkbox **habilitado** solo si el protocolo cumple regla de negocio “validado para envío” (misma regla que habilita acciones de PDF masivo en backend). Filas no elegibles: checkbox deshabilitado + `opacity-30` como en muestras |
| Acción masiva | Solo envío desde `show` | FAB visible cuando `selectedIds.length > 0`: texto “Enviar **N** seleccionado(s)” |
| Modal | No existe | Modal **único** con destino email + atajos + mensaje opcional + lista de protocolos + confirmación |
| Paginación | Links Laravel | Selección válida **solo en la página visible** (igual patrón implícito que listados paginados). Si en el futuro se necesita selección multi-página, sería otra versión |
| Empty state / colspan | `colspan` fijo en fila vacía | Ajustar `colspan` al sumar una columna (+1) |

---

## Layout — Sección nueva en la tabla

### Cabecera

- **Columna 1:** checkbox maestro (`selectAll`), alineado izquierda, estilo `rounded border-gray-300 text-teal-600 focus:ring-teal-500`.
- Resto de columnas **sin cambios de orden** respecto al diseño actual (Protocolo, Fecha, Paciente, OS, …).

### Cuerpo por fila

- Checkbox enlazado a `selectedIds` (Alpine); `@disabled` cuando la fila no es elegible.

### Barra flotante (FAB)

- Posición: `fixed bottom-6 right-6 z-50` (por encima de paginación pero sin tapar contenido crítico en mobile; si el FAB tapara algo en viewport muy bajo, permitir `bottom-4` y `max-md:left-4 max-md:right-4` opcional — baja prioridad).

---

## Modal — “Enviar informes por email”

### Encabezado

- Título: **Enviar informes por email** (o “Enviar protocolos seleccionados”).
- Botón cerrar (X) mismo patrón que muestras.

### Bloque 1 — Resumen y advertencias

- Si hay protocolos seleccionados que **no** pueden enviarse (no validados según regla): lista amarilla tipo `bg-yellow-50 border border-yellow-200` con texto tipo *“No se incluirán en el envío: …”* con número de protocolo (equivalente UX a `batchData.skipped` en muestras).
- Si todos los seleccionados son enviables: omitir bloque o mostrar línea neutra *“Se enviarán N protocolos en un solo correo.”*

### Bloque 2 — Destinatario (único)

- **Label:** “Correo del destinatario”.
- **Input** `type="email"` obligatorio antes de enviar, ancho completo, placeholders neutros (`ejemplo@dominio.com`).
- **Botones secundarios** en una fila debajo o al lado del label (no competir con el submit):

  | Botón | Habilitación | Acción |
  |-------|----------------|--------|
  | “Usar email de obra social” | Todos los seleccionados comparten la misma obra social **y** esa OS tiene `email` en datos | Rellena el input con ese email |
  | “Usar email del paciente” | Todos los seleccionados son del **mismo** paciente **y** el paciente tiene `email` | Rellena el input |

- Si la condición no cumple: botón `disabled` + `opacity-50` **o** oculto; preferencia UX: **visible deshabilitado** con `title` explicativo (“Los protocolos seleccionados no comparten la misma obra social”) para educar al usuario.

### Bloque 3 — Mensaje opcional

- `textarea` opcional, 3–4 filas visibles, label “Mensaje para el cuerpo del correo (opcional)” — coherente con el formulario de envío en detalle si existe.

### Bloque 4 — Lista compacta de protocolos a adjuntar

- Lista con viñetas o chips: `C-2026-…` por cada admisión que **sí** entrará en el lote (solo las válidas tras filtrar). Texto auxiliar en `text-xs text-gray-500`: “Todos los PDFs se adjuntarán en un solo mensaje.”

### Pie del modal

- **Cancelar:** `border border-gray-300 text-gray-700 rounded-lg` (secundario).
- **Enviar:** `bg-teal-600 text-white`, estado loading “Enviando…” + `disabled` + opacidad.
- Opcional: segunda confirmación solo si N > umbral (ej. 15) — **fuera de alcance** salvo que producto lo pida; documentar como mejora futura.

---

## Estados de la interfaz

| Estado | Comportamiento visual |
|--------|------------------------|
| Sin selección | FAB oculto (`x-show` / `x-cloak`) |
| Con selección | FAB visible |
| Modal abierto | Scroll interno si el contenido supera `max-h-[90vh]` (`overflow-y-auto` en el panel, como muestras) |
| Enviando | Botón primario deshabilitado; opcional spinner Bootstrap Icons `bi-arrow-repeat` animado si el dev ya usa íconos |
| Éxito | Panel resultado inline debajo del modal o banner verde (reutilizar estilo flash existente del layout); si se usa JSON como muestras: líneas “Enviados: …”, “Saltados: …”, “Errores: …” en `text-sm` con colores green/yellow/red |
| Error de red | Mensaje rojo breve + posibilidad de reintentar sin perder selección si es viable |

---

## Accesibilidad y claridad

- Focus trap en modal: idealmente primer campo email al abrir (comportamiento Alpine/Livewire según implementación).
- Contraste: textos de ayuda en `text-gray-600`, no solo gris claro sobre blanco.
- El usuario debe entender **antes de enviar** que es **un solo correo** con **varios adjuntos**, no un correo por protocolo.

---

## Qué NO cambiar

- Bloque de filtros GET (formulario superior): mantener layout y botones Filtrar / Limpiar.
- Header “Admisiones de Pacientes” y botón “Nueva Admisión”.
- Columnas de montos, badges de sede/facturación y enlaces Ver / PDF por fila (salvo el incremento de `colspan` en empty state).
- Paginación Laravel al pie de la tabla.

---

## Contrato mínimo para Alpine (referencia de diseño, no implementación)

El programador puede exponer en el HTML por fila un meta JSON o `data-*` con: `id`, `protocol_number`, flags `can_batch_send`, `insurance_id`, `patient_id`, `insurance_email`, `patient_email` (solo lo necesario para habilitar atajos sin N+1 en cliente si los datos vienen del servidor en primera carga).

---

## Entrega al programador

1. Implementar según este documento **y** el prompt `v1.86.0-envio-masivo-protocolos-clinicos-email.md`.
2. Ante duda visual, priorizar **paridad con** `sample/index` **y** **tokens del listado de admisiones**.

---

## Mockup visual

No se generó imagen: la pantalla es una extensión directa de dos vistas existentes; el wireframe textual + referencias de archivo bastan para implementar sin ambigüedad relevante.
