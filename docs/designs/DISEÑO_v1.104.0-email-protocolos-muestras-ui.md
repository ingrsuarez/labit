# Diseño: v1.104.0 — Email en protocolos de muestras (aguas / alimentos / hielo)

> Documento de diseño para **AGENTE_PROGRAMADOR** (contexto obligatorio antes de implementar).
> **Modo:** Mejora de pantallas existentes (detalle + listado).
> **Diseñado por:** AGENTE_DESIGNER (sesión PM 2026-05-19).
> **Insumos:** v1.104.0 acordado en planificación PM, `DESIGN_SYSTEM.md`, referencias `lab/admissions/show`, `vet/admissions/show`, `sample/show`, `sample/index`.

---

## Propósito

Completar la **paridad de envío por correo** en el módulo **Protocolos de Muestras** respecto a laboratorio clínico y veterinario: el usuario debe poder enviar un informe desde el **detalle del protocolo** y gestionar **envíos masivos** desde el **listado**, con permisos y feedback claros.

**Usuarios:** Bioquímico, Recepción lab, Técnico lab, Administrador (permiso `samples-reports.send`).

**Acción principal en detalle:** Enviar PDF de resultados validados al cliente por email.  
**Acción principal en listado:** Seleccionar N protocolos validados y enviar un correo por cliente (varios PDFs adjuntos).

---

## Reglas de negocio con impacto en UI

| Regla | Implicación de diseño |
|--------|------------------------|
| Envío **individual**: ≥1 determinación validada (paridad clínico/vet) | Botón visible cuando `$validatedCount > 0`, mismo criterio que “Ver PDF”. |
| Envío **masivo**: solo protocolos **100% validados** | Checkbox habilitado solo si `calculated_status === 'validated'`. Tooltip en checkbox deshabilitado: “Solo protocolos completamente validados”. |
| Permiso `samples-reports.send` | Toda UI de email envuelta en `@can('samples-reports.send')`. Sin permiso: no checkboxes de lote, no FAB/banner, no botón en show. |
| Reenvío permitido | Protocolos con badge “Enviado” siguen pudiendo enviarse de nuevo (individual y masivo). Tras éxito, actualizar `sent_at`. |
| Destinatario por defecto | Precargar `customer.email`. Chips de atajo si hay email del cliente. |
| Backend ya implementado | No rediseñar flujo de negocio; solo UI, permisos, alineación de reglas y discoverability. |

---

## Paleta y consistencia del módulo Muestras

- **Color acento del módulo:** `teal-600` / `teal-700` (botones primarios, FAB, focus rings).
- **Botón “Enviar por Email” en detalle:** `bg-teal-600 hover:bg-teal-700` (no usar `purple` del clínico ni `amber` del vet — el usuario está en contexto muestras).
- **Icono:** sobre SVG inline existente en el proyecto (`M3 8l7.89 5.26…`) o `bi-envelope`.
- **Modal:** `rounded-xl shadow-2xl`, overlay `bg-black/50`, cierre con Escape y click fuera (patrón vet).

---

## 1. Vista detalle — `sample/show.blade.php`

### Layout — zona de acciones (header sticky)

Orden sugerido de botones (izq → der, después de navegación Anterior/Siguiente), **solo si aplica cada `@can`**:

1. Cargar Resultados (`teal`)
2. Validar (`yellow`)
3. **Ver PDF** (`green`) — si `$validatedCount > 0`
4. **Descargar** (`green-100`) — si `$validatedCount > 0`
5. **Enviar por Email** (`teal-600`, mismo peso visual que Ver PDF) — **nuevo**, si `$validatedCount > 0` y `@can('samples-reports.send')`
6. Imprimir Etiqueta (`purple`)
7. Editar (`indigo`)
8. Eliminar (`red`, condicional)

El botón de email va **inmediatamente después** de Descargar PDF y **antes** de Etiqueta, agrupando acciones de “informe”.

### Botón

```
[icono sobre] Enviar por Email
```

- `inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700`
- En mobile: puede acortarse a “Email” con `hidden sm:inline` en el texto largo si el header se satura (opcional).

### Modal de envío individual

**Título:** `Enviar resultados por email`

| Campo | Tipo | Detalle |
|-------|------|---------|
| Email destinatario * | `input[type=email]` | Valor inicial: `$sample->customer?->email` |
| Atajos | Chips clickeables | Si `customer.email`: chip `Cliente: {email}` |
| Mensaje personalizado | `textarea` 3 filas | Opcional, placeholder: “Mensaje opcional para incluir en el email…” |
| Cancelar | secundario | `bg-gray-100` |
| Enviar | primario | `bg-teal-600` |

- Form: `POST` → `route('sample.sendEmail', $sample)`
- Alpine: extender `x-data` del header a `{ showEmailModal: false }` (o `x-data` en contenedor padre que ya use el sticky header).
- Si protocolo ya enviado (`$sample->isSent()`): texto auxiliar bajo el título del modal: *“Este protocolo ya fue enviado el {fecha}. Podés reenviarlo.”* (`text-sm text-sky-700`)

### Estados

| Estado | UI |
|--------|-----|
| Sin determinaciones validadas | Sin botón Email; no modal |
| Con validadas, sin permiso send | Sin botón |
| Envío exitoso (redirect) | Flash verde existente en la vista |
| Error validación backend | Flash rojo: mensaje del controller |

### Accesibilidad

- `aria-label` en botón si solo muestra icono en breakpoint chico.
- Focus trap no obligatorio (patrón actual del proyecto); sí `@keydown.escape.window`.

---

## 2. Vista listado — `sample/index.blade.php`

### Problema actual

- FAB flotante abajo a la derecha: **poco visible** frente al banner inline del listado clínico (`lab/admissions/index`).
- Checkboxes y modal **sin** `@can('samples-reports.send')`.
- Columna checkbox visible para todos los roles con `samples.section`.

### Cambios propuestos

#### 2.1 Barra de selección (reemplazar o complementar FAB)

**Patrón preferido (igual que lab clínico):** cuando `selectedIds.length > 0`, mostrar **banner inline** encima de la tabla:

```
┌─────────────────────────────────────────────────────────────────┐
│ 3 protocolo(s) seleccionado(s). Podés enviar los informes      │
│ por email (agrupados por cliente).          [Enviar por email] │
└─────────────────────────────────────────────────────────────────┘
```

- Contenedor: `rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 mb-4`
- Botón: `bg-teal-600 text-white rounded-lg`
- Solo dentro de `@can('samples-reports.send')`

**FAB flotante:** eliminar o mantener solo en mobile si el banner no entra bien — **recomendación: eliminar FAB** y usar solo banner para una sola fuente de verdad.

#### 2.2 Columna de checkboxes

- Envolver `<th>` y `<td>` de checkbox en `@can('samples-reports.send')`.
- Si no tiene permiso: tabla sin columna checkbox (ajustar `colspan` del empty state de 9 → 8).

#### 2.3 Checkbox por fila

- Habilitado: `calculated_status === 'validated'` (mantener).
- Deshabilitado: `disabled:opacity-30` + `title="Solo protocolos completamente validados"`.
- **Reenvío:** protocolos con badge “Enviado” **siguen** habilitados si están validados.

#### 2.4 Header “seleccionar todos”

- `toggleAll()` solo selecciona filas visibles con `is_validated === true` en `sampleMeta` (ya existe; verificar coherencia con filtro Alpine).

#### 2.5 Modal masivo existente

Mantener estructura actual (grupos por cliente, email editable, “Saltear”, aviso de no validados). Ajustes menores:

| Elemento | Ajuste |
|----------|--------|
| Título | `Enviar protocolos por email` |
| Botón confirmar | `bg-teal-600` (ya está) |
| Toast resultado | Mantener abajo-izquierda; añadir ícono ✓/⚠ según resultado |
| Errores 403 | Si `fetch` devuelve 403, toast: “No tenés permiso para enviar informes por email.” |

#### 2.6 Hint de discoverability (opcional, baja prioridad)

Bajo filtros, línea de ayuda solo para quien tiene permiso:

`Tip: seleccioná protocolos validados para enviarlos por email al cliente.`

`text-xs text-gray-400`

---

## 3. Flujos de interacción

### Flujo A — Envío desde detalle

1. Usuario abre protocolo con al menos 1 determinación validada.
2. Clic en **Enviar por Email**.
3. Modal con email del cliente precargado; opcionalmente edita mensaje.
4. **Enviar** → POST → redirect con flash éxito; badge “Enviado” visible en header si aplica.

### Flujo B — Envío masivo desde listado

1. Usuario filtra listado (ej. tipo Agua, estado Validado).
2. Marca checkboxes de protocolos validados (mismo u distinto cliente).
3. Aparece banner “N protocolo(s) seleccionado(s)…”.
4. Clic **Enviar por email** → modal con grupos por cliente.
5. Revisa emails, marca “Saltear” si corresponde → **Enviar**.
6. Toast con enviados / salteados / errores → “Cerrar y actualizar” recarga listado.

---

## 4. Componentes y stack

| Pieza | Uso |
|-------|-----|
| `<x-lab-layout>` | Sin cambios |
| Alpine.js | `showEmailModal`, `selectedIds`, `showBatchModal` (existente) |
| Tailwind | Clases del design system |
| Blade `@can` | `samples-reports.send` |
| Sin Livewire | Esta versión no introduce componentes Livewire |

**Referencias de implementación (copiar patrón, adaptar colores):**

- Modal individual: `resources/views/vet/admissions/show.blade.php` (líneas ~348-407)
- Banner selección: `resources/views/lab/admissions/index.blade.php` (líneas ~100-116)
- Modal masivo: ya en `sample/index.blade.php` (conservar)

---

## 5. Qué NO cambiar

- Plantilla PDF (`sample/pdf-mpdf`) y Mailables existentes.
- Agrupación por cliente en envío masivo.
- Filtros Alpine del listado (búsqueda, tipo, estado, sede).
- Orden y estilo del header sticky del detalle (v1.95.0).
- Módulos clínico y veterinario.

---

## 6. Criterios de aceptación visual (QA)

- [ ] En detalle de protocolo validado parcial, se ve **Enviar por Email** junto a Ver/Descargar PDF.
- [ ] Usuario sin `samples-reports.send` no ve checkboxes ni botones de email.
- [ ] En listado, al seleccionar protocolos validados aparece **banner teal** (no solo FAB oculto).
- [ ] Checkbox deshabilitado muestra tooltip explicativo.
- [ ] Modal individual cierra con Escape y Cancelar.
- [ ] Tras envío masivo exitoso, badge “Enviado” visible al recargar.
- [ ] Coherencia de color **teal** en acciones de email del módulo muestras.

---

## 7. Mockups

No se generan imágenes: el layout replica patrones ya existentes en lab/vet con ajuste de color teal. Suficiente con este documento + vistas de referencia.
