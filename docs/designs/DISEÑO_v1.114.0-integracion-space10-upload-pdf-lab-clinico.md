# Diseño UI — v1.114.0 Integración Space10 (upload PDF lab clínico)

**Versión:** v1.114.0  
**Pantallas:** `resources/views/lab/admissions/index.blade.php`, `resources/views/lab/admissions/show.blade.php` (mejora; layout `x-lab-layout`)  
**Modo Designer:** mejora de pantalla existente  
**Fecha:** 2026-06-06  
**Referencia interna:** extensión del patrón v1.86.0 (barra de selección + modal batch + badge “Enviado”); acción secundaria independiente del email.

---

## Propósito

Permitir al personal del laboratorio subir informes PDF de protocolos clínicos validados al historial del paciente en **Space10**, identificando al paciente por DNI. La subida puede ocurrir:

1. **Automáticamente** al enviar el informe por email (individual o masivo), si aún no fue subido.
2. **Manualmente en lote** desde el listado de admisiones, sin enviar email.

**Usuario:** roles con permiso de ver admisiones y enviar informes (`lab-admissions.show`, alineado a envío de email).

**Acción principal (batch):** seleccionar filas → “Subir a Space10” → confirmar → ver resultado por protocolo.

**Principio UX clave:** no perder tiempo re-subiendo. Los protocolos ya subidos a Space10 deben ser **visibles** y **omitidos** automáticamente.

---

## Patrón de consistencia (no reinventar)

| Elemento | Origen en el proyecto |
|----------|----------------------|
| Barra teal de selección (`border-teal-200 bg-teal-50`) | `lab/admissions/index.blade.php` (v1.86.0) |
| Modal centrado overlay `bg-black bg-opacity-40`, panel `rounded-2xl shadow-2xl max-w-2xl` | Mismo modal de email masivo |
| Badge secundario junto a estado (“Enviado” sky) | Columna Estado actual |
| Resultado JSON inline (`uploaded` / `skipped` / `errors`) | Modal email masivo (`sent` / `skipped` / `errors`) |
| Botón primario teal / secundario gris | Tokens existentes del listado |

**Color distintivo Space10:** usar **violet/indigo** (`bg-violet-100 text-violet-800`) para diferenciar de “Enviado” (sky) y no confundir estados de email vs portal clínico.

---

## Cambios propuestos — Listado (`index.blade.php`)

| Elemento | Estado actual | Propuesta |
|----------|---------------|-----------|
| Columna Estado | Badge protocolo + badge “Enviado” si `sent_at` | Agregar badge **“Space10 ✓”** (`violet`) si `space10_uploaded_at` no es null. Tooltip: “Subido a Space10 el DD/MM/YYYY HH:mm” |
| Barra de selección | Solo botón “Enviar por email” | Segundo botón **“Subir a Space10”** (`bg-violet-600 hover:bg-violet-700`, icono nube/flecha arriba). Visible con la misma condición `selectedIds.length > 0` |
| FAB flotante inferior | Solo “Enviar N seleccionado(s)” | **No duplicar** acción Space10 en FAB — mantener FAB solo para email; Space10 vive en la barra superior teal (menos ruido, dos acciones claras en un solo lugar) |
| Checkbox elegibilidad | Habilitado si ≥1 determinación validada | **Batch Space10:** habilitado si validado + paciente con DNI (`patientId` no vacío). Si ya subido a Space10: checkbox **sigue habilitado** (el usuario puede seleccionarlo) pero el backend lo salta con motivo “ya subido” |
| Filtro estado | “Enviado” (`sent_at`) | **Fuera de alcance UI** en v1.114.0: no agregar filtro “Subido Space10” salvo que producto lo pida; el badge en fila alcanza |

---

## Barra de selección — layout propuesto

Cuando hay protocolos seleccionados, la barra teal muestra:

```
[N] protocolo(s) seleccionado(s).  [Subir a Space10]  [Enviar por email]
```

- En **mobile** (`flex-wrap`): botones apilados a la derecha o en fila completa debajo del texto.
- Orden visual: acción Space10 a la **izquierda** del email (secundaria respecto al flujo habitual de email, pero visible).
- Si `SPACE10_ENABLED=false` (config): ocultar botón Space10 y badges; sin cambios en email.

---

## Modal — “Subir informes a Space10”

Modal **separado** del de email (no mezclar flujos). Patrón visual idéntico al modal de email masivo.

### Encabezado

- Título: **Subir informes a Space10**
- Subtítulo (`text-sm text-gray-500`): “Se subirá un PDF por protocolo al historial del paciente en Space10 (por DNI).”

### Bloque 1 — Resumen

- Línea neutra: “Se intentará subir **N** protocolo(s).”
- Bloque amarillo (`bg-yellow-50 border-yellow-200`) si hay seleccionados **no elegibles** (sin validar, sin DNI): lista con número de protocolo y motivo corto.
- Bloque gris informativo si hay seleccionados **ya subidos**: “Se omitirán X protocolo(s) ya presentes en Space10.” (preview client-side desde meta `space10_uploaded_at` / flag `already_on_space10`)

### Bloque 2 — Lista compacta

- Chips o viñetas: `C-2026-… — DNI 12345678` por cada protocolo que **sí** se intentará subir (excluye ya subidos y no elegibles en la lista principal; opcional pestaña “Omitidos” solo si N omitidos > 3 — **preferencia:** una sola lista con secciones “A subir” / “Omitidos”).

### Pie del modal

- **Cancelar:** secundario gris.
- **Subir a Space10:** `bg-violet-600 text-white`, loading “Subiendo…” + disabled.
- **Sin campo email** — a diferencia del modal de email.

### Resultado post-envío (inline en modal, mismo patrón que email)

| Clave JSON | Color | Ejemplo |
|------------|-------|---------|
| `uploaded` | verde | “Subidos: C-2026-001, C-2026-002” |
| `skipped` | amarillo | “Omitidos: C-2026-003 (ya subido a Space10)” |
| `errors` | rojo | “Errores: C-2026-004 (paciente no encontrado en Space10)” |

Botón “Cerrar y actualizar listado” → reload para refrescar badges.

---

## Detalle del protocolo (`show.blade.php`)

### Badge en cabecera / bloque de acciones

Junto al indicador de envío por email (si existe), mostrar badge **Space10 ✓** cuando `space10_uploaded_at` está seteado.

### Feedback tras envío de email individual

Tras POST exitoso de `sendEmail`:

- Flash **success** existente se mantiene.
- Si Space10 upload en el mismo request:
  - **OK:** agregar línea al flash o flash secundario: “Informe subido a Space10.”
  - **Skip (ya subido):** no agregar ruido.
  - **Error Space10:** flash **warning** (`amber`): “Email enviado. No se pudo subir a Space10: [motivo]. Podés reintentar desde el listado.”

No bloquear el éxito del email por fallo de Space10.

---

## Estados de la interfaz

| Estado | Comportamiento visual |
|--------|------------------------|
| Space10 deshabilitado (env) | Sin botón batch, sin badges Space10 |
| Protocolo validado, sin DNI | Checkbox batch deshabilitado o elegible solo para email; tooltip “Sin DNI — no se puede subir a Space10” |
| Ya subido a Space10 | Badge violet “Space10 ✓”; en batch → skipped |
| Subida en curso | Modal: botón disabled + “Subiendo…” |
| Email OK + Space10 falla | Warning amber en show; listado sin badge hasta reintento exitoso |

---

## Accesibilidad y claridad

- Diferenciar siempre **Enviado** (email, sky) vs **Space10 ✓** (portal, violet).
- Tooltips en badges con fecha de subida.
- El usuario debe entender que batch Space10 sube **un PDF por protocolo**, no un zip ni un solo archivo agrupado.

---

## Qué NO cambiar

- Modal y flujo de **email masivo** existente (v1.86.0).
- Filtros GET del listado (salvo futuro filtro Space10).
- Columnas de montos, sede, facturación, enlaces Ver/PDF/Email por fila.
- Módulos vet y muestras.

---

## Contrato mínimo para Alpine / meta por fila

Extender `admissionMeta` JSON con:

| Campo | Uso |
|-------|-----|
| `can_batch_space10` | validado + DNI presente + Space10 enabled |
| `already_on_space10` | `space10_uploaded_at !== null` |
| `patient_dni` | `patientId` para preview en modal |
| `space10_uploaded_at` | ISO o null (opcional, para tooltip en badge server-side) |

Función Alpine sugerida: `admissionBatchSpace10()` o extender `admissionBatchMail()` con estado separado (`showSpace10Modal`, `batchSpace10Result`) — decisión de implementación del dev; diseño pide **estado modal independiente** del email.

---

## Entrega al programador

1. Implementar según este documento **y** el prompt `v1.114.0-integracion-space10-upload-pdf-lab-clinico.md`.
2. Backend: columna `space10_uploaded_at`, servicio HTTP, hooks en `sendEmail` / `batchEmail`, ruta `batch-space10`.
3. Ante duda visual, priorizar paridad con modal email v1.86.0 y badges existentes en columna Estado.

---

## Mockup visual

No se generó imagen: extensión directa de UI existente; wireframe textual + referencias de archivo bastan.
