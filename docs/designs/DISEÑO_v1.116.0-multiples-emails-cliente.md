# Diseño: v1.116.0 / v1.116.1 — Múltiples emails por cliente y envío multi-destinatario

> Documento de diseño para **AGENTE_PROGRAMADOR** (contexto obligatorio antes de implementar).
> **Modo:** Mejora de pantallas existentes + componente reutilizable nuevo.
> **Diseñado por:** AGENTE_DESIGNER (sesión PM 2026-06-07).
> **Insumos:** planificación PM, `DESIGN_SYSTEM.md`, `customer/create|edit`, `insurance/edit|index`, modales email en `sample/show`, `sample/index`, `vet/admissions/show`, `lab/admissions/show`.

---

## Propósito

Permitir que **obras sociales** (`Insurance`) y **clientes** (`Customer`: aguas, veterinario, obra social, etc.) registren **varios correos con etiqueta** (Resultados, Facturación, Pagos, Otro) y que el envío de protocolos llegue a **todos los destinatarios seleccionados en un solo correo** (campo **Para:** con N direcciones).

**Usuarios:** Administración, Recepción lab, Bioquímico (ABM de clientes/OS; envío de protocolos).

**Fuera de scope visual:** pacientes (`patients.email`), presupuestos, facturación automática por etiqueta.

---

## Reglas de negocio con impacto en UI

| Regla | Implicación de diseño |
|--------|------------------------|
| Cada entidad puede tener 0..N emails | Lista dinámica en ABM; no obligar al menos uno |
| Un email es **principal** (`is_primary`) | Radio o estrella; sincroniza columna legacy `email` |
| Etiqueta opcional pero recomendada | Select con presets + "Otro" con input texto |
| Presets de etiqueta | `Resultados`, `Facturación`, `Pagos`, `Otro` |
| Envío protocolos: todos en **Para:** | Un solo `Mail::to([...])`; no CC ni BCC |
| "Enviar a todos" | Chip/botón que selecciona todos los emails de la entidad |
| Reenvío permitido | Sin cambio; modales igual que hoy |
| Compatibilidad legacy | Listados y código viejo siguen leyendo `customers.email` / `insurances.email` (= principal) |

---

## Componente reutilizable — `Emails repeater`

Usar en **Clientes** (create/edit) y **Obras sociales** (create/edit + modal rápido en index si aplica).

### Ubicación en formulario

Reemplazar el input único `Email` en la sección **Información de contacto** por un bloque:

**Título de sección:** `Correos electrónicos`  
**Subtítulo auxiliar:** `Podés agregar varios correos. El principal se usa por defecto al enviar protocolos.`

### Estructura de cada fila (Alpine.js `x-data`)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Correos electrónicos                                    [+ Agregar email] │
├─────────────────────────────────────────────────────────────────────────┤
│  (●) Principal   [ Etiqueta ▼ Resultados ]  [ email@empresa.com      ] [🗑] │
│  ( )             [ Etiqueta ▼ Facturación ] [ facturacion@empresa.com] [🗑] │
│  ( )             [ Etiqueta ▼ Otro      ] [ otro@...               ] [🗑] │
│                                              ↑ input texto si Otro        │
└─────────────────────────────────────────────────────────────────────────┘
```

### Campos por fila

| Campo | Tipo | Detalle |
|-------|------|---------|
| Principal | `radio` `name="primary_email_index"` | Solo uno activo; al marcar otro, desmarca el anterior |
| Etiqueta | `select` | Opciones: vacío, Resultados, Facturación, Pagos, Otro |
| Etiqueta custom | `input text` | Visible solo si etiqueta = Otro; `maxlength=50` |
| Email | `input[type=email]` | Requerido si la fila existe |
| Eliminar | botón icono | `bi-trash`; deshabilitado si es la única fila (o permite vaciar todo) |

### Nombres de request (sugeridos para backend)

```html
emails[0][email]
emails[0][label]        <!-- valor final: preset o texto custom -->
emails[0][is_primary]   <!-- 1 o 0 -->
```

### Comportamiento Alpine

- Estado inicial en **edit**: cargar `$customer->emails` o `$insurance->emails`; si vacío pero hay `->email` legacy, una fila con ese valor y `is_primary=true`, etiqueta vacía.
- Estado inicial en **create**: una fila vacía opcional o cero filas + botón "Agregar email".
- **Agregar email**: push fila vacía; si es la primera, marcar como principal.
- **Eliminar**: quitar fila; si se elimina la principal, la primera restante pasa a principal.
- Validación client-side ligera: formato email antes de submit (opcional; backend manda).

### Estilos

| Contexto | Acento |
|----------|--------|
| Clientes (`customer/*`) | `zinc-500` / `focus:ring-zinc-500` (existente) |
| Obras sociales (`insurance/*`) | `blue-500` / `focus:ring-blue-500` (existente) |

Botón agregar: `inline-flex items-center gap-1 text-sm text-{accent}-600 hover:text-{accent}-800` + `bi-plus-lg`.

Filas: `border border-gray-200 rounded-lg p-3 mb-2 bg-gray-50`.

---

## Listados — `customer/index` e `insurance/index`

### Columna Email

| Caso | Render |
|------|--------|
| Sin emails | `—` |
| 1 email | `resultados@x.com` (principal) |
| N emails | Línea 1: principal. Línea 2: `text-xs text-gray-500` → `+2 correos más` (tooltip o title con lista completa) |

Tooltip/title: `Resultados: a@x.com · Facturación: b@x.com`.

---

## Modales de envío — v1.116.1 (actualización)

Patrón común para `sample/show`, `sample/index` (batch), `vet/admissions/show`, `lab/admissions/show`.

### Chips de atajo

Reemplazar chips de un solo email por **un chip por cada email de la entidad**:

```
[ Resultados · admin@os.com ]  [ Facturación · fact@os.com ]  [ Todos (2) ]
```

| Chip | Acción |
|------|--------|
| Individual | Rellena el input con ese email solo |
| **Todos (N)** | Rellena con todos los emails separados por coma, o envía array `emails[]` al backend |

**Estilo chips por módulo** (mantener consistencia v1.104.0):

| Módulo | Color chip entidad |
|--------|-------------------|
| Muestras (cliente) | `border-teal-400 text-teal-700 bg-teal-50` |
| Veterinario (customer) | `border-amber-400 text-amber-700 bg-amber-50` |
| Lab clínico (obra social) | `border-purple-400 text-purple-700 bg-purple-50` |
| Lab clínico (paciente) | Sin cambio — chip `👤` paciente, un solo email |

Chip **Todos**: `border-gray-400 text-gray-700 bg-gray-100 font-medium`.

### Campo destinatario

- Label: `Email destinatario *` → `Destinatario(s) *`
- Input: acepta **un email** o **varios separados por coma** (el operador puede editar manualmente).
- Placeholder: `correo@ejemplo.com` o `uno@x.com, otro@x.com`
- Texto ayuda bajo input: `Podés elegir un correo, varios separados por coma, o usar "Todos".`

### Valor por defecto al abrir modal

- **Muestras / Vet:** email **principal** del `customer` (comportamiento actual, ampliado con chips).
- **Lab clínico:** email del **paciente** si existe; chips de OS muestran todos los de la obra social.

### Batch masivo muestras (`sample/index`)

En cada grupo por cliente del modal masivo:

- Mostrar chips de todos los emails del cliente (si hay más de uno).
- Input email del grupo: por defecto **todos** los emails del cliente unidos por coma (si el cliente tiene N>1), o el único si N=1.
- Si el cliente no tiene emails: grupo en estado actual (`Sin email`, skip).

---

## Estados vacíos y errores

| Estado | UI |
|--------|-----|
| Cliente sin emails en ABM | Permitido guardar; listado muestra `—` |
| Envío sin destinatario válido | Flash/error JSON: `El cliente no tiene correos configurados` |
| Email inválido en repeater | Error de validación Laravel por fila |
| Duplicados en repeater | Backend rechaza o deduplica (mensaje: `No se permiten correos duplicados`) |

---

## Accesibilidad

- `aria-label` en botones eliminar y agregar.
- Labels visibles en cada input de email.
- Chips como `button type="button"` con texto descriptivo (`Resultados: email@...`).

---

## Wireframe ASCII — Modal envío (muestras)

```
┌──────────────────────────────────────────┐
│  Enviar resultados por email              │
├──────────────────────────────────────────┤
│  Destinatario(s) *                        │
│  [ Resultados · c@x.com ] [ Todos (2) ]   │
│  ┌────────────────────────────────────┐  │
│  │ c@x.com, fact@x.com                │  │
│  └────────────────────────────────────┘  │
│  Podés elegir un correo, varios...        │
│                                           │
│  Mensaje opcional                         │
│  ┌────────────────────────────────────┐  │
│  │                                    │  │
│  └────────────────────────────────────┘  │
│              [ Cancelar ]  [ Enviar ]     │
└──────────────────────────────────────────┘
```

---

## Archivos Blade a tocar (referencia DEV)

| Archivo | Cambio |
|---------|--------|
| `resources/views/customer/create.blade.php` | Repeater emails |
| `resources/views/customer/edit.blade.php` | Repeater emails |
| `resources/views/customer/index.blade.php` | Columna +N |
| `resources/views/insurance/edit.blade.php` | Repeater emails |
| `resources/views/insurance/index.blade.php` | Modal create + columna +N |
| `resources/views/sample/show.blade.php` | Chips multi + v1.116.1 |
| `resources/views/sample/index.blade.php` | Batch groups chips + v1.116.1 |
| `resources/views/vet/admissions/show.blade.php` | Chips customer multi |
| `resources/views/lab/admissions/show.blade.php` | Chips OS multi |

**Opcional:** partial Blade `resources/views/components/entity-emails-repeater.blade.php` + `resources/views/components/email-recipient-chips.blade.php` para DRY.

---

## Notas para implementación

- La columna legacy `email` debe actualizarse al guardar (= email con `is_primary=true`).
- Helper modelo: `recipientEmails(): array` — orden: principal primero, luego `sort_order`.
- Auditoría envío: loguear string `implode(', ', $emails)` como hoy.
- No filtrar por etiqueta en v1.116.1; "Todos" envía a todos los registrados.
